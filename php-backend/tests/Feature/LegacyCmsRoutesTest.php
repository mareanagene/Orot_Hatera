<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyCmsRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('username')->unique();
            $table->text('password_hash');
            $table->boolean('is_admin')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('site_content', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('page_name');
            $table->string('lang_code', 8)->default('he');
            $table->string('section_id');
            $table->text('headline')->nullable();
            $table->text('body_text')->nullable();
            $table->text('image_url')->nullable();
        });

        Schema::create('farm_cards', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('page_name');
            $table->string('lang_code', 8)->default('he');
            $table->string('card_key');
            $table->string('card_type')->default('farm');
            $table->text('title')->nullable();
            $table->text('body_text')->nullable();
            $table->string('bg_color')->nullable();
            $table->string('text_color')->nullable();
            $table->integer('width_units')->default(1);
            $table->integer('sort_order')->default(0);
            $table->integer('row_group')->default(1);
            $table->text('image_url')->nullable();
            $table->integer('image_scale')->nullable();
            $table->integer('card_height')->nullable();
            $table->integer('image_height')->nullable();
            $table->integer('image_card_width')->nullable();
            $table->integer('image_x')->nullable();
            $table->integer('image_radius')->nullable();
            $table->text('caption')->nullable();
            $table->text('link_url')->nullable();
            $table->string('link_label')->nullable();
            $table->boolean('link_is_download')->default(false);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('org_team_members', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('page_name');
            $table->string('lang_code', 8)->default('he');
            $table->integer('tier_index')->default(0);
            $table->integer('slot_index')->default(0);
            $table->string('full_name')->nullable();
            $table->string('role_title')->nullable();
            $table->text('role_detail')->nullable();
            $table->text('image_url')->nullable();
        });

        Schema::create('portfolio_projects', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('page_name');
            $table->string('lang_code', 8)->default('he');
            $table->integer('sort_order')->default(0);
            $table->string('title')->nullable();
            $table->text('summary')->nullable();
            $table->text('body_text')->nullable();
            $table->text('image_url')->nullable();
            $table->text('gallery_json')->nullable();
        });

        Schema::create('contact_inquiries', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        DB::table('users')->insert([
            'id' => 1,
            'username' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
            'is_admin' => 1,
            'created_at' => now(),
        ]);

        DB::table('site_content')->insert([
            ['page_name' => 'farm_1', 'lang_code' => 'he', 'section_id' => 'brand_title', 'headline' => 'אורות הטירב ביצוע 1998 בע"מ', 'body_text' => null, 'image_url' => null],
            ['page_name' => 'farm_1', 'lang_code' => 'he', 'section_id' => 'brand_tagline', 'headline' => 'תאורת כבישים ותשתיות', 'body_text' => null, 'image_url' => null],
            ['page_name' => 'farm_1', 'lang_code' => 'he', 'section_id' => 'hero_title', 'headline' => "תאורה\nחכמה", 'body_text' => null, 'image_url' => '/static/uploads/test.jpg'],
            ['page_name' => 'farm_1', 'lang_code' => 'he', 'section_id' => 'hero_image', 'headline' => '', 'body_text' => null, 'image_url' => '/static/uploads/test.jpg'],
            ['page_name' => 'ceo_story', 'lang_code' => 'he', 'section_id' => 'page_title', 'headline' => 'דבר המייסד', 'body_text' => null, 'image_url' => null],
            ['page_name' => 'ceo_story', 'lang_code' => 'he', 'section_id' => 'page_intro', 'headline' => '', 'body_text' => 'הסיפור האישי והחזון שמובילים את החברה.', 'image_url' => null],
            ['page_name' => 'ceo_story', 'lang_code' => 'he', 'section_id' => 'document_version', 'headline' => '1.0.0', 'body_text' => null, 'image_url' => null],
            ['page_name' => 'ceo_story', 'lang_code' => 'he', 'section_id' => 'ceo_name', 'headline' => 'מנכ"ל לדוגמה', 'body_text' => null, 'image_url' => null],
            ['page_name' => 'ceo_story', 'lang_code' => 'he', 'section_id' => 'ceo_role', 'headline' => 'מייסד ומנכ"ל', 'body_text' => null, 'image_url' => null],
            ['page_name' => 'ceo_story', 'lang_code' => 'he', 'section_id' => 'ceo_quote', 'headline' => 'בונים חברה עם דרך, לא רק עם פרויקטים.', 'body_text' => null, 'image_url' => null],
            ['page_name' => 'ceo_story', 'lang_code' => 'he', 'section_id' => 'ceo_story', 'headline' => '', 'body_text' => 'מגיל צעיר חיפשתי איך להוביל פרויקטים עם אחריות אמיתית.', 'image_url' => null],
            ['page_name' => 'ceo_story', 'lang_code' => 'he', 'section_id' => 'ceo_vision', 'headline' => '', 'body_text' => 'החזון הוא לבנות תשתיות תאורה אמינות לאורך שנים.', 'image_url' => null],
            ['page_name' => 'ceo_story', 'lang_code' => 'he', 'section_id' => 'ceo_highlights', 'headline' => '', 'body_text' => "מוביל את החברה כבר שנים\nמאמין באמינות ובשירות", 'image_url' => null],
            ['page_name' => 'ceo_story', 'lang_code' => 'he', 'section_id' => 'ceo_image', 'headline' => '', 'body_text' => null, 'image_url' => '/static/uploads/ceo.jpg'],
        ]);

        DB::table('farm_cards')->insert([
            'page_name' => 'farm_1',
            'lang_code' => 'he',
            'card_key' => 'card_1',
            'card_type' => 'farm',
            'title' => 'כותרת כרטיס',
            'body_text' => 'טקסט כרטיס',
            'bg_color' => '#eef1f6',
            'text_color' => '#1f2937',
            'width_units' => 1,
            'sort_order' => 1,
            'row_group' => 1,
            'card_height' => 260,
            'image_height' => 120,
            'image_card_width' => 100,
            'link_url' => 'https://example.com/spec.pdf',
            'link_label' => 'הורדת מפרט',
            'link_is_download' => 1,
            'is_active' => 1,
        ]);

        DB::table('org_team_members')->insert([
            'page_name' => 'farm_1',
            'lang_code' => 'he',
            'tier_index' => 0,
            'slot_index' => 0,
            'full_name' => 'מנכ"ל לדוגמה',
            'role_title' => 'מנכ"ל',
            'role_detail' => 'אחריות מלאה',
            'image_url' => '/static/uploads/member.jpg',
        ]);

        DB::table('portfolio_projects')->insert([
            'page_name' => 'farm_1',
            'lang_code' => 'he',
            'sort_order' => 0,
            'title' => 'פרויקט לדוגמה',
            'summary' => 'תקציר פרויקט',
            'body_text' => 'פירוט מלא',
            'image_url' => '/static/uploads/project.jpg',
            'gallery_json' => json_encode(['/static/uploads/project.jpg']),
        ]);
    }

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('אורות הטירב ביצוע 1998 בע"מ')->assertSee('1.0.0')->assertSee('office@orot-l.com')->assertSee('הורדת מפרט');
        $this->get('/team')->assertOk()->assertSee('מנכ"ל לדוגמה');
        $this->get('/projects')->assertOk()->assertSee('פרויקט לדוגמה');
        $this->get('/ceo-message')->assertOk()->assertSee('דבר המייסד')->assertSee('הסיפור האישי');
        $this->get('/login')->assertOk()->assertSee('כניסה למערכת');
    }

    public function test_admin_routes_redirect_guest_to_login(): void
    {
        $this->get('/editor')->assertRedirectContains('/login');
        $this->get('/editor/team')->assertRedirectContains('/login');
        $this->get('/editor/projects')->assertRedirectContains('/login');
        $this->get('/editor/ceo-message')->assertRedirectContains('/login');
        $this->get('/editor/contacts')->assertRedirectContains('/login');
        $this->get('/users')->assertRedirectContains('/login');
    }

    public function test_admin_routes_render_for_admin_session(): void
    {
        $this->withSession(['user_id' => 1])->get('/editor')->assertOk()->assertSee('עורך האתר');
        $this->withSession(['user_id' => 1])->get('/editor/team')->assertOk()->assertSee('עץ ארגון');
        $this->withSession(['user_id' => 1])->get('/editor/projects')->assertOk()->assertSee('פרויקטים');
        $this->withSession(['user_id' => 1])->get('/editor/ceo-message')->assertOk()->assertSeeText('עורך דף המייסד')->assertSee('1.0.0');
        $this->withSession(['user_id' => 1])->get('/editor/contacts')->assertOk()->assertSee('פניות צור קשר');
        $this->withSession(['user_id' => 1])->get('/users')->assertOk()->assertSee('ניהול משתמשים');
    }

    public function test_contact_api_saves_inquiry(): void
    {
        $this->postJson('/api/contact', [
            'full_name' => 'ישראל ישראלי',
            'email' => 'test@example.com',
            'phone' => '050-1234567',
            'note' => 'בדיקת API',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('contact_inquiries', [
            'full_name' => 'ישראל ישראלי',
            'email' => 'test@example.com',
        ]);
    }

    public function test_legacy_scrypt_user_can_login_and_is_upgraded(): void
    {
        DB::table('users')->insert([
            'id' => 2,
            'username' => 'legacy-user',
            'password_hash' => 'scrypt:32768:8:1$olcJcy8C1Tvmts5S$36d3fb9405b8492196b04a262771239a8edebf0e29d335235c5f8a608df06f5e27e4a2454dd392c781ec529994c7dbde54ff48de4cc55cac08cb4a90c0b0966a',
            'is_admin' => 1,
            'created_at' => now(),
        ]);

        $this->post('/login', [
            'username' => 'legacy-user',
            'password' => 'legacy-pass',
            'next' => '/editor',
        ])->assertRedirect('/editor');

        $newHash = DB::table('users')->where('username', 'legacy-user')->value('password_hash');
        $this->assertIsString($newHash);
        $this->assertStringStartsWith('$2y$', $newHash);
    }

    public function test_upload_api_requires_admin_and_returns_url(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->post('/api/upload-image', ['image' => $file])->assertRedirectContains('/login');

        $response = $this->withSession(['user_id' => 1])
            ->post('/api/upload-image', ['image' => $file])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $url = $response->json('url');
        $this->assertIsString($url);
        $this->assertStringStartsWith('/uploads/', $url);
    }

    public function test_file_upload_api_requires_admin_and_returns_url(): void
    {
        $file = UploadedFile::fake()->create('spec-sheet.pdf', 32, 'application/pdf');

        $this->post('/api/upload-file', ['file' => $file])->assertRedirectContains('/login');

        $response = $this->withSession(['user_id' => 1])
            ->post('/api/upload-file', ['file' => $file])
            ->assertOk()
            ->assertJsonStructure(['url', 'original_name']);

        $url = $response->json('url');
        $this->assertIsString($url);
        $this->assertStringStartsWith('/uploads/', $url);
        $this->assertSame('spec-sheet.pdf', $response->json('original_name'));
    }
}
