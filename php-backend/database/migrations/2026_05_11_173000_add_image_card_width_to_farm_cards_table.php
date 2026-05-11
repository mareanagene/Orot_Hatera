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
            if (!Schema::hasColumn('farm_cards', 'image_card_width')) {
                $table->unsignedInteger('image_card_width')->nullable()->after('image_height');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('farm_cards') || !Schema::hasColumn('farm_cards', 'image_card_width')) {
            return;
        }

        Schema::table('farm_cards', function (Blueprint $table): void {
            $table->dropColumn('image_card_width');
        });
    }
};
