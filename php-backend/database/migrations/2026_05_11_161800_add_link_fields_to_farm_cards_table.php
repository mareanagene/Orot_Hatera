<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('farm_cards')) {
            return;
        }

        Schema::table('farm_cards', function (Blueprint $table): void {
            if (!Schema::hasColumn('farm_cards', 'link_url')) {
                $table->string('link_url', 1000)->nullable()->after('caption');
            }
            if (!Schema::hasColumn('farm_cards', 'link_label')) {
                $table->string('link_label', 255)->nullable()->after('link_url');
            }
            if (!Schema::hasColumn('farm_cards', 'link_is_download')) {
                $table->boolean('link_is_download')->default(false)->after('link_label');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('farm_cards')) {
            return;
        }

        Schema::table('farm_cards', function (Blueprint $table): void {
            $drops = [];
            if (Schema::hasColumn('farm_cards', 'link_is_download')) {
                $drops[] = 'link_is_download';
            }
            if (Schema::hasColumn('farm_cards', 'link_label')) {
                $drops[] = 'link_label';
            }
            if (Schema::hasColumn('farm_cards', 'link_url')) {
                $drops[] = 'link_url';
            }
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
