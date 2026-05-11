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
            if (!Schema::hasColumn('farm_cards', 'card_height')) {
                $table->unsignedInteger('card_height')->nullable()->after('image_scale');
            }
            if (!Schema::hasColumn('farm_cards', 'image_height')) {
                $table->unsignedInteger('image_height')->nullable()->after('card_height');
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
            if (Schema::hasColumn('farm_cards', 'image_height')) {
                $drops[] = 'image_height';
            }
            if (Schema::hasColumn('farm_cards', 'card_height')) {
                $drops[] = 'card_height';
            }
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
