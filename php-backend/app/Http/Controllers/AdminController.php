<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LegacyCms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function editor(Request $request)
    {
        $pageName = trim((string) $request->input('page_name', 'farm_1')) ?: 'farm_1';
        $message = '';

        if ($request->isMethod('post')) {
            $this->saveEditorPage($request, $pageName);
            $message = 'השינויים נשמרו.';
        }

        $content = LegacyCms::getPageContent($pageName, 'he');

        return view('editor', [
            'page_name' => $pageName,
            'pages' => $this->pages(),
            'content' => $content,
            'farm_cards' => LegacyCms::getFarmCards($pageName, 'he'),
            'current_user' => LegacyCms::currentUser($request),
            'message' => $message,
            'default_hero_image_url' => $content['hero_image_url'] ?: LegacyCms::DEFAULT_HERO_IMAGE_URL,
        ]);
    }

    public function editorTeam(Request $request)
    {
        $pageName = trim((string) $request->input('page_name', 'farm_1')) ?: 'farm_1';
        $message = '';

        if ($request->isMethod('post')) {
            $members = [];
            $count = max(0, (int) $request->input('members_count', 0));
            for ($i = 0; $i < $count; $i++) {
                $fullName = trim((string) $request->input("full_name_{$i}", ''));
                $roleTitle = trim((string) $request->input("role_title_{$i}", ''));
                $roleDetail = trim((string) $request->input("role_detail_{$i}", ''));
                $imageUrl = trim((string) $request->input("image_url_{$i}", ''));
                if ($fullName === '' && $roleTitle === '' && $roleDetail === '' && $imageUrl === '') {
                    continue;
                }
                $members[] = [
                    'tier_index' => max(0, (int) $request->input("tier_{$i}", 0)),
                    'slot_index' => count($members),
                    'full_name' => $fullName,
                    'role_title' => $roleTitle,
                    'role_detail' => $roleDetail,
                    'image_url' => $imageUrl,
                ];
            }

            LegacyCms::replaceOrgTeamMembers($pageName, 'he', $members);
            $message = 'עץ הארגון נשמר.';
        }

        return view('editor_team', [
            'page_name' => $pageName,
            'pages' => $this->pages(),
            'members' => LegacyCms::getOrgTeamMembers($pageName, 'he'),
            'current_user' => LegacyCms::currentUser($request),
            'message' => $message,
        ]);
    }

    public function editorProjects(Request $request)
    {
        $pageName = trim((string) $request->input('page_name', 'farm_1')) ?: 'farm_1';
        $message = '';

        if ($request->isMethod('post')) {
            $projects = [];
            $count = max(0, (int) $request->input('projects_count', 0));
            for ($i = 0; $i < $count; $i++) {
                $title = trim((string) $request->input("title_{$i}", ''));
                $summary = trim((string) $request->input("summary_{$i}", ''));
                $bodyText = trim((string) $request->input("body_text_{$i}", ''));
                $images = LegacyCms::normalizePortfolioImageUrls($request->input("gallery_json_{$i}", '[]'));
                $project = [
                    'title' => $title,
                    'summary' => $summary,
                    'body_text' => $bodyText,
                    'images' => $images,
                ];
                if (!LegacyCms::portfolioProjectRowHasContent($project)) {
                    continue;
                }
                $projects[] = $project;
            }

            LegacyCms::replacePortfolioProjects($pageName, 'he', $projects);
            $message = 'הפרויקטים נשמרו.';
        }

        return view('editor_projects', [
            'page_name' => $pageName,
            'pages' => $this->pages(),
            'projects' => LegacyCms::getPortfolioProjects($pageName, 'he'),
            'current_user' => LegacyCms::currentUser($request),
            'message' => $message,
        ]);
    }

    public function editorCeoMessage(Request $request)
    {
        $message = '';

        if ($request->isMethod('post')) {
            $this->saveCeoMessagePage($request);
            $message = 'דף המייסד והמנכ"ל נשמר.';
        }

        return view('editor_ceo_message', [
            'content' => LegacyCms::getPageContent('ceo_story', 'he'),
            'current_user' => LegacyCms::currentUser($request),
            'message' => $message,
        ]);
    }

    public function editorContacts(Request $request)
    {
        $inquiries = DB::table('contact_inquiries')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return view('editor_contacts', [
            'inquiries' => $inquiries,
            'current_user' => LegacyCms::currentUser($request),
        ]);
    }

    public function users(Request $request)
    {
        $error = '';
        $success = '';

        if ($request->isMethod('post')) {
            $username = trim((string) $request->input('username', ''));
            $password = (string) $request->input('password', '');

            if ($username === '' || strlen($username) < 3) {
                $error = 'שם המשתמש חייב להכיל לפחות 3 תווים.';
            } elseif (strlen($password) < 6) {
                $error = 'הסיסמה חייבת להכיל לפחות 6 תווים.';
            } elseif (User::query()->where('username', $username)->exists()) {
                $error = 'שם המשתמש כבר קיים.';
            } else {
                User::query()->create([
                    'username' => $username,
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                    'is_admin' => 0,
                ]);
                $success = 'המשתמש נוצר בהצלחה.';
            }
        }

        return view('users', [
            'users' => User::query()->orderBy('created_at')->get(),
            'error' => $error,
            'success' => $success,
            'current_user' => LegacyCms::currentUser($request),
        ]);
    }

    private function pages(): array
    {
        return DB::table('site_content')
            ->distinct()
            ->orderBy('page_name')
            ->pluck('page_name')
            ->filter()
            ->values()
            ->all();
    }

    private function saveEditorPage(Request $request, string $pageName): void
    {
        LegacyCms::upsertSiteContentRow(
            $pageName,
            'he',
            'brand_title',
            LegacyCms::DEFAULT_BRAND_TITLE,
            null,
            null
        );
        LegacyCms::upsertSiteContentRow(
            $pageName,
            'he',
            'brand_tagline',
            trim((string) $request->input('brand_tagline', '')),
            null,
            null
        );
        LegacyCms::upsertSiteContentRow(
            $pageName,
            'he',
            'hero_title',
            trim((string) $request->input('hero_title', '')),
            null,
            trim((string) $request->input('hero_image', ''))
        );
        LegacyCms::upsertSiteContentRow(
            $pageName,
            'he',
            'hero_image',
            '',
            null,
            trim((string) $request->input('hero_image', ''))
        );

        $cards = [];
        $count = max(0, (int) $request->input('cards_count', 0));
        for ($i = 0; $i < $count; $i++) {
            $cardKey = trim((string) $request->input("card_key_{$i}", ''));
            $title = trim((string) $request->input("card_title_{$i}", ''));
            $bodyText = trim((string) $request->input("card_body_{$i}", ''));
            $imageUrl = trim((string) $request->input("card_image_{$i}", ''));
            $caption = trim((string) $request->input("card_caption_{$i}", ''));
            $linkUrl = trim((string) $request->input("card_link_url_{$i}", ''));
            $linkLabel = trim((string) $request->input("card_link_label_{$i}", ''));
            if ($cardKey === '' && $title === '' && $bodyText === '' && $imageUrl === '' && $caption === '' && $linkUrl === '' && $linkLabel === '') {
                continue;
            }

            $cards[] = [
                'card_key' => $cardKey !== '' ? $cardKey : 'card_'.($i + 1),
                'card_type' => trim((string) $request->input("card_type_{$i}", 'farm')) ?: 'farm',
                'title' => $title,
                'body_text' => $bodyText,
                'bg_color' => trim((string) $request->input("card_bg_{$i}", LegacyCms::DEFAULT_CARD_BG_COLOR)) ?: LegacyCms::DEFAULT_CARD_BG_COLOR,
                'text_color' => trim((string) $request->input("card_text_{$i}", LegacyCms::DEFAULT_CARD_TEXT_COLOR)) ?: LegacyCms::DEFAULT_CARD_TEXT_COLOR,
                'width_units' => max(1, min(3, (int) $request->input("card_width_{$i}", 1))),
                'sort_order' => (int) $request->input("card_sort_{$i}", $i + 1),
                'row_group' => max(1, (int) $request->input("card_row_{$i}", 1)),
                'image_url' => $imageUrl,
                'image_scale' => max(30, min(200, (int) $request->input("card_scale_{$i}", 100))),
                'card_height' => max(140, min(700, (int) $request->input("card_height_{$i}", LegacyCms::DEFAULT_CARD_HEIGHT))),
                'image_height' => max(80, min(520, (int) $request->input("card_image_height_{$i}", LegacyCms::DEFAULT_CARD_IMAGE_HEIGHT))),
                'image_card_width' => max(30, min(100, (int) $request->input("card_image_width_{$i}", LegacyCms::DEFAULT_IMAGE_CARD_WIDTH))),
                'image_x' => max(-100, min(100, (int) $request->input("card_x_{$i}", 0))),
                'image_radius' => max(0, min(50, (int) $request->input("card_radius_{$i}", 0))),
                'caption' => $caption,
                'link_url' => $linkUrl,
                'link_label' => $linkLabel,
                'link_is_download' => $request->boolean("card_link_download_{$i}"),
                'is_active' => $request->boolean("card_active_{$i}"),
            ];
        }

        LegacyCms::replaceFarmCards($pageName, 'he', $cards);
    }

    private function saveCeoMessagePage(Request $request): void
    {
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'page_title',
            trim((string) $request->input('page_title', 'דבר המייסד והמנכ"ל')),
            null,
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'page_intro',
            '',
            trim((string) $request->input('page_intro', '')),
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'document_version',
            trim((string) $request->input('document_version', '1.0.0')) ?: '1.0.0',
            null,
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_name',
            trim((string) $request->input('ceo_name', '')),
            null,
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_role',
            trim((string) $request->input('ceo_role', '')),
            null,
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_quote',
            trim((string) $request->input('ceo_quote', '')),
            null,
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_story',
            '',
            trim((string) $request->input('ceo_story', '')),
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_vision',
            '',
            trim((string) $request->input('ceo_vision', '')),
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_highlights',
            '',
            trim((string) $request->input('ceo_highlights', '')),
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_image',
            '',
            null,
            trim((string) $request->input('ceo_image', ''))
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_current_name',
            trim((string) $request->input('ceo_current_name', '')),
            null,
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_current_role',
            trim((string) $request->input('ceo_current_role', '')),
            null,
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_current_quote',
            trim((string) $request->input('ceo_current_quote', '')),
            null,
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_current_story',
            '',
            trim((string) $request->input('ceo_current_story', '')),
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_current_vision',
            '',
            trim((string) $request->input('ceo_current_vision', '')),
            null
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_current_image',
            '',
            null,
            trim((string) $request->input('ceo_current_image', ''))
        );
        LegacyCms::upsertSiteContentRow(
            'ceo_story',
            'he',
            'ceo_gallery',
            '',
            trim((string) $request->input('ceo_gallery', '')),
            null
        );
    }
}
