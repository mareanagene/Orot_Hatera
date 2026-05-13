<?php

namespace App\Http\Controllers;

use App\Support\LegacyCms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_ATTEMPT_WINDOW_SECONDS = 900;
    private const LOGIN_LOCKOUT_SECONDS = 900;

    public function index(Request $request)
    {
        $content = LegacyCms::getPageContent('farm_1', 'he');
        $founderContent = LegacyCms::getPageContent('ceo_story', 'he');
        $farmCards = LegacyCms::getFarmCards('farm_1', 'he');
        $projects = collect(explode("\n", (string) ($content['recent_projects_body'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        return view('index', [
            'content' => $content,
            'document_version' => $founderContent['document_version'] ?? '1.0.0',
            'projects' => $projects,
            'farm_cards' => $farmCards,
            'current_user' => LegacyCms::currentUser($request),
        ]);
    }

    public function team(Request $request)
    {
        $content = LegacyCms::getPageContent('farm_1', 'he');
        $members = LegacyCms::getOrgTeamMembers('farm_1', 'he');

        return view('team', [
            'content' => $content,
            'team_tiers' => LegacyCms::groupOrgTeamByTier($members),
            'current_user' => LegacyCms::currentUser($request),
        ]);
    }

    public function projects(Request $request)
    {
        return view('projects', [
            'content' => LegacyCms::getPageContent('farm_1', 'he'),
            'portfolio_projects' => LegacyCms::getPortfolioProjects('farm_1', 'he'),
            'current_user' => LegacyCms::currentUser($request),
        ]);
    }

    public function ceoMessage(Request $request)
    {
        $siteContent = LegacyCms::getPageContent('farm_1', 'he');
        $ceoContent = LegacyCms::getPageContent('ceo_story', 'he');
        $content = $siteContent;

        foreach ($ceoContent as $key => $value) {
            if (str_starts_with($key, 'ceo_') || str_starts_with($key, 'page_')) {
                $content[$key] = $value;
            }
        }

        return view('ceo_message', [
            'content' => $content,
            'ceo_gallery_items' => $this->ceoGalleryItems($content),
            'current_user' => LegacyCms::currentUser($request),
        ]);
    }

    private function ceoGalleryItems(array $content): array
    {
        $items = [];
        $raw = (string) ($content['ceo_gallery_body'] ?? $content['ceo_gallery'] ?? '');

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            [$imageUrl, $caption] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            if ($imageUrl === '') {
                continue;
            }

            $items[] = [
                'image_url' => $imageUrl,
                'caption' => $caption !== '' ? $caption : 'עוד רגע של עשייה והמשכיות.',
            ];
        }

        if (!empty($items)) {
            return $items;
        }

        $fallbacks = [
            [
                'image_url' => trim((string) ($content['ceo_image_image_url'] ?? '')),
                'caption' => 'המייסד שהניח את היסודות לערכי המקצועיות, האחריות והאמינות.',
            ],
            [
                'image_url' => trim((string) ($content['ceo_current_image_image_url'] ?? '')),
                'caption' => 'הדור הבא ממשיך את הדרך ומוביל את החברה קדימה.',
            ],
        ];

        foreach ($fallbacks as $item) {
            if ($item['image_url'] !== '') {
                $items[] = $item;
            }
        }

        return $items;
    }

    public function loginForm(Request $request)
    {
        if (LegacyCms::currentUser($request)) {
            return redirect()->route('index');
        }

        $nextUrl = $this->safeNextUrl($request->query('next', ''));

        return view('login', [
            'error' => '',
            'next_url' => $nextUrl,
        ]);
    }

    public function login(Request $request)
    {
        if (LegacyCms::currentUser($request)) {
            return redirect()->route('index');
        }

        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');
        $nextUrl = $this->safeNextUrl((string) $request->input('next', ''));

        [$locked, $retryAfter] = $this->isLoginLocked($username, $request);
        if ($locked) {
            return view('login', [
                'error' => "יותר מדי ניסיונות התחברות. נסו שוב בעוד {$retryAfter} שניות.",
                'next_url' => $nextUrl,
            ]);
        }

        $user = LegacyCms::findUserByUsername($username);
        if ($user && LegacyCms::verifyPassword($user, $password)) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('user_id', $user->id);
            $request->session()->regenerate();
            LegacyCms::maybeUpgradePasswordHash($user, $password);
            $this->clearFailedLogin($username, $request);
            return redirect($nextUrl ?: route('index'));
        }

        $this->recordFailedLogin($username, $request);

        return view('login', [
            'error' => 'שם משתמש או סיסמה שגויים.',
            'next_url' => $nextUrl,
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('index');
    }

    private function safeNextUrl(string $target): string
    {
        $target = trim($target);
        if ($target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return route('index');
        }
        $parts = parse_url($target);
        if (($parts['scheme'] ?? null) || ($parts['host'] ?? null)) {
            return route('index');
        }
        return $target;
    }

    private function attemptKey(string $username, Request $request): string
    {
        $forwarded = trim(explode(',', (string) $request->header('X-Forwarded-For', ''))[0] ?? '');
        $ip = $forwarded !== '' ? $forwarded : ($request->ip() ?: 'unknown');
        return 'login:'.$ip.':'.strtolower($username);
    }

    private function isLoginLocked(string $username, Request $request): array
    {
        $attempts = Cache::get($this->attemptKey($username, $request), []);
        $cutoff = time() - self::LOGIN_ATTEMPT_WINDOW_SECONDS;
        $attempts = array_values(array_filter($attempts, fn ($ts) => $ts >= $cutoff));
        Cache::put($this->attemptKey($username, $request), $attempts, self::LOGIN_LOCKOUT_SECONDS);

        if (count($attempts) < self::MAX_LOGIN_ATTEMPTS) {
            return [false, 0];
        }

        $retryAfter = max(1, self::LOGIN_LOCKOUT_SECONDS - (time() - $attempts[count($attempts) - self::MAX_LOGIN_ATTEMPTS]));
        return [$retryAfter > 0, $retryAfter];
    }

    private function recordFailedLogin(string $username, Request $request): void
    {
        $key = $this->attemptKey($username, $request);
        $attempts = Cache::get($key, []);
        $attempts[] = time();
        Cache::put($key, $attempts, self::LOGIN_LOCKOUT_SECONDS);
    }

    private function clearFailedLogin(string $username, Request $request): void
    {
        Cache::forget($this->attemptKey($username, $request));
    }
}
