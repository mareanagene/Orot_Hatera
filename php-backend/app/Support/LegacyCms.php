<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Vinsaj9\Crypto\Scrypt\scrypt;

class LegacyCms
{
    public const DEFAULT_BRAND_TITLE = 'אורות הטירה ביצוע 1998 בע"מ';
    public const DEFAULT_HERO_IMAGE_URL = 'https://d3m9l0v76dty0.cloudfront.net/system/photos/12981922/original/4ff5da7517599bc31bc3f8880056e880.jpg';
    public const DEFAULT_CARD_BG_COLOR = '#eef1f6';
    public const DEFAULT_CARD_TEXT_COLOR = '#1f2937';
    public const DEFAULT_CARD_LINK_LABEL = 'למעבר';
    public const DEFAULT_CARD_HEIGHT = 270;
    public const DEFAULT_CARD_IMAGE_HEIGHT = 140;
    public const DEFAULT_IMAGE_CARD_WIDTH = 100;
    public const MAX_PORTFOLIO_IMAGES = 40;
    public const MAX_PORTFOLIO_IMAGE_URL_LEN = 500;

    public static function currentUser(Request $request): ?array
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return null;
        }

        $user = User::query()->find($userId);
        if (!$user) {
            $request->session()->forget('user_id');
            return null;
        }

        return [
            'id' => (int) $user->id,
            'username' => (string) $user->username,
            'is_admin' => (bool) $user->is_admin,
        ];
    }

    public static function findUserByUsername(string $username): ?User
    {
        return User::query()->where('username', trim($username))->first();
    }

    public static function verifyPassword(User $user, string $password): bool
    {
        $hash = (string) ($user->password_hash ?? '');
        if ($hash === '') {
            return false;
        }

        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$argon2')) {
            return password_verify($password, $hash);
        }

        if (str_starts_with($hash, 'scrypt:')) {
            try {
                // Legacy Flask hashes may take noticeably longer to verify in PHP.
                @ini_set('max_execution_time', '180');
                @set_time_limit(180);
                [$method, $salt, $hashValue] = explode('$', $hash, 3);
                [, $n, $r, $p] = explode(':', $method, 4);
                $actual = scrypt($password, $salt, (int) $n, (int) $r, (int) $p, strlen($hashValue) / 2);
                return hash_equals($hashValue, $actual);
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }

    public static function maybeUpgradePasswordHash(User $user, string $password): void
    {
        $hash = (string) ($user->password_hash ?? '');
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$argon2')) {
            return;
        }

        $user->password_hash = password_hash($password, PASSWORD_BCRYPT);
        $user->save();
    }

    public static function getPageContent(string $pageName, string $langCode = 'he'): array
    {
        $content = [
            'brand_title' => '',
            'brand_tagline' => '',
            'hero_title' => '',
            'hero_image' => '',
            'hero_image_url' => '',
            'page_title' => '',
            'page_intro' => '',
            'document_version' => '1.0.0',
            'ceo_name' => '',
            'ceo_role' => '',
            'ceo_quote' => '',
            'ceo_story' => '',
            'ceo_vision' => '',
            'ceo_highlights' => '',
            'ceo_current_name' => '',
            'ceo_current_role' => '',
            'ceo_current_quote' => '',
            'ceo_current_story' => '',
            'ceo_current_vision' => '',
            'ceo_current_image' => '',
            'ceo_gallery' => '',
            'contact_title' => '',
            'contact_name_placeholder' => '',
            'contact_company_placeholder' => '',
            'contact_phone_value' => '',
            'live_title' => '',
            'live_name_label' => '',
            'live_details_label' => '',
            'live_submit_label' => '',
            'projects_title' => '',
            'recent_projects_body' => '',
        ];

        $rows = DB::table('site_content')
            ->select(['section_id', 'headline', 'body_text', 'image_url'])
            ->where('page_name', $pageName)
            ->where('lang_code', $langCode)
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $sectionId = trim((string) ($row->section_id ?? ''));
            if ($sectionId === '') {
                continue;
            }
            if ($sectionId === 'hero_image') {
                $content['hero_image'] = trim((string) ($row->image_url ?? ''));
            } else {
                $content[$sectionId] = (string) ($row->headline ?: ($row->body_text ?: ''));
            }
            if (!empty($row->body_text)) {
                $content[$sectionId.'_body'] = (string) $row->body_text;
            }
            if (!empty($row->image_url)) {
                $content[$sectionId.'_image_url'] = (string) $row->image_url;
                if (in_array($sectionId, ['hero_title', 'hero_image'], true)) {
                    $content['hero_image_url'] = (string) $row->image_url;
                }
            }
        }

        $heroImage = trim((string) ($content['hero_image'] ?? ''));
        if ($heroImage === 'hero_image') {
            $heroImage = '';
            $content['hero_image'] = '';
        }

        $heroImageUrl = trim((string) ($content['hero_image_url'] ?? ''));
        if ($heroImageUrl === 'hero_image') {
            $heroImageUrl = '';
            $content['hero_image_url'] = '';
        }

        if ($heroImage !== '') {
            $content['hero_image_url'] = $heroImage;
        } elseif ($heroImageUrl !== '') {
            $content['hero_image'] = $heroImageUrl;
        }

        if (trim((string) ($content['recent_projects_body'] ?? '')) === '') {
            $content['recent_projects_body'] = (string) ($content['projects_title_body'] ?? '');
        }

        $content['brand_title'] = self::DEFAULT_BRAND_TITLE;

        return $content;
    }

    public static function upsertSiteContentRow(string $pageName, string $langCode, string $sectionId, string $headline, ?string $bodyText, ?string $imageUrl): void
    {
        DB::table('site_content')->updateOrInsert(
            [
                'page_name' => $pageName,
                'lang_code' => $langCode,
                'section_id' => $sectionId,
            ],
            [
                'headline' => $headline,
                'body_text' => $bodyText ?: null,
                'image_url' => $imageUrl ?: null,
            ]
        );
    }

    public static function getFarmCards(string $pageName, string $langCode = 'he'): array
    {
        return DB::table('farm_cards')
            ->select([
                'id', 'card_key', 'card_type', 'title', 'body_text', 'bg_color', 'text_color',
                'width_units', 'sort_order', 'row_group', 'image_url', 'image_scale',
                'card_height', 'image_height', 'image_card_width',
                'image_x', 'image_radius', 'caption', 'link_url', 'link_label', 'link_is_download', 'is_active',
            ])
            ->where('page_name', $pageName)
            ->where('lang_code', $langCode)
            ->orderBy('row_group')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public static function replaceFarmCards(string $pageName, string $langCode, array $cards): void
    {
        DB::transaction(function () use ($pageName, $langCode, $cards): void {
            DB::table('farm_cards')
                ->where('page_name', $pageName)
                ->where('lang_code', $langCode)
                ->delete();

            foreach ($cards as $index => $card) {
                DB::table('farm_cards')->insert([
                    'page_name' => $pageName,
                    'lang_code' => $langCode,
                    'card_key' => $card['card_key'],
                    'card_type' => $card['card_type'] ?? 'farm',
                    'title' => $card['title'] ?? '',
                    'body_text' => $card['body_text'] ?: null,
                    'bg_color' => $card['bg_color'] ?? self::DEFAULT_CARD_BG_COLOR,
                    'text_color' => $card['text_color'] ?? self::DEFAULT_CARD_TEXT_COLOR,
                    'width_units' => $card['width_units'] ?? 1,
                    'sort_order' => $card['sort_order'] ?? ($index + 1),
                    'row_group' => $card['row_group'] ?? 1,
                    'image_url' => !empty($card['image_url']) ? $card['image_url'] : null,
                    'image_scale' => $card['image_scale'] ?? 100,
                    'card_height' => $card['card_height'] ?? self::DEFAULT_CARD_HEIGHT,
                    'image_height' => $card['image_height'] ?? self::DEFAULT_CARD_IMAGE_HEIGHT,
                    'image_card_width' => $card['image_card_width'] ?? self::DEFAULT_IMAGE_CARD_WIDTH,
                    'image_x' => $card['image_x'] ?? 0,
                    'image_radius' => $card['image_radius'] ?? 0,
                    'caption' => !empty($card['caption']) ? $card['caption'] : null,
                    'link_url' => !empty($card['link_url']) ? $card['link_url'] : null,
                    'link_label' => !empty($card['link_label']) ? $card['link_label'] : null,
                    'link_is_download' => !empty($card['link_is_download']) ? 1 : 0,
                    'is_active' => !empty($card['is_active']) ? 1 : 0,
                ]);
            }
        });
    }

    public static function getOrgTeamMembers(string $pageName, string $langCode = 'he'): array
    {
        return DB::table('org_team_members')
            ->select(['id', 'tier_index', 'slot_index', 'full_name', 'role_title', 'role_detail', 'image_url'])
            ->where('page_name', $pageName)
            ->where('lang_code', $langCode)
            ->orderBy('tier_index')
            ->orderBy('slot_index')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public static function groupOrgTeamByTier(array $members): array
    {
        $tiers = [];
        foreach ($members as $member) {
            $tier = (int) ($member['tier_index'] ?? 0);
            $tiers[$tier] ??= [];
            $tiers[$tier][] = $member;
        }
        ksort($tiers);
        foreach ($tiers as &$group) {
            usort($group, fn ($a, $b) => ((int) ($a['slot_index'] ?? 0)) <=> ((int) ($b['slot_index'] ?? 0)));
        }
        return array_values($tiers);
    }

    public static function replaceOrgTeamMembers(string $pageName, string $langCode, array $members): void
    {
        DB::transaction(function () use ($pageName, $langCode, $members): void {
            DB::table('org_team_members')
                ->where('page_name', $pageName)
                ->where('lang_code', $langCode)
                ->delete();

            foreach ($members as $member) {
                DB::table('org_team_members')->insert([
                    'page_name' => $pageName,
                    'lang_code' => $langCode,
                    'tier_index' => (int) ($member['tier_index'] ?? 0),
                    'slot_index' => (int) ($member['slot_index'] ?? 0),
                    'full_name' => trim((string) ($member['full_name'] ?? '')),
                    'role_title' => trim((string) ($member['role_title'] ?? '')),
                    'role_detail' => trim((string) ($member['role_detail'] ?? '')) ?: null,
                    'image_url' => trim((string) ($member['image_url'] ?? '')) ?: null,
                ]);
            }
        });
    }

    public static function normalizePortfolioImageUrls(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $url) {
            $value = trim((string) $url);
            if ($value === '' || strlen($value) > self::MAX_PORTFOLIO_IMAGE_URL_LEN) {
                continue;
            }
            $out[] = $value;
            if (count($out) >= self::MAX_PORTFOLIO_IMAGES) {
                break;
            }
        }

        return $out;
    }

    public static function galleryImagesFromRow(array $row): array
    {
        $urls = self::normalizePortfolioImageUrls($row['gallery_json'] ?? null);
        if (!empty($urls)) {
            return $urls;
        }
        $imageUrl = trim((string) ($row['image_url'] ?? ''));
        return $imageUrl !== '' ? [$imageUrl] : [];
    }

    public static function portfolioProjectRowHasContent(array $project): bool
    {
        return trim((string) ($project['title'] ?? '')) !== ''
            || trim((string) ($project['summary'] ?? '')) !== ''
            || trim((string) ($project['body_text'] ?? '')) !== ''
            || !empty($project['images']);
    }

    public static function getPortfolioProjects(string $pageName, string $langCode = 'he'): array
    {
        $rows = DB::table('portfolio_projects')
            ->select(['id', 'sort_order', 'title', 'summary', 'body_text', 'image_url', 'gallery_json'])
            ->where('page_name', $pageName)
            ->where('lang_code', $langCode)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                $item = (array) $row;
                $item['images'] = self::galleryImagesFromRow($item);
                return $item;
            })
            ->all();

        return $rows;
    }

    public static function replacePortfolioProjects(string $pageName, string $langCode, array $projects): void
    {
        DB::transaction(function () use ($pageName, $langCode, $projects): void {
            DB::table('portfolio_projects')
                ->where('page_name', $pageName)
                ->where('lang_code', $langCode)
                ->delete();

            foreach (array_values($projects) as $index => $project) {
                $images = self::normalizePortfolioImageUrls($project['images'] ?? []);
                DB::table('portfolio_projects')->insert([
                    'page_name' => $pageName,
                    'lang_code' => $langCode,
                    'sort_order' => $index,
                    'title' => trim((string) ($project['title'] ?? '')),
                    'summary' => trim((string) ($project['summary'] ?? '')),
                    'body_text' => trim((string) ($project['body_text'] ?? '')) ?: null,
                    'image_url' => $images[0] ?? null,
                    'gallery_json' => !empty($images) ? json_encode($images, JSON_UNESCAPED_UNICODE) : null,
                ]);
            }
        });
    }

    public static function looksLikeEmail(string $value): bool
    {
        $value = strtolower(trim($value));
        if (strlen($value) < 5 || substr_count($value, '@') !== 1) {
            return false;
        }
        [$local, $domain] = explode('@', $value, 2);
        return $local !== '' && $domain !== '' && str_contains($domain, '.') && !str_contains($value, ' ');
    }

    public static function normalizeContactPhone(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (strlen($value) > 40 || str_contains($value, "\n") || str_contains($value, "\r")) {
            return null;
        }
        return $value;
    }
}
