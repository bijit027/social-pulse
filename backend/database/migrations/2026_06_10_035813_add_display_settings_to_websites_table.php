<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->integer('display_for')->default(5)->after('is_active');
            $table->integer('display_last')->default(20)->after('display_for');
            $table->integer('display_from_days')->default(30)->after('display_last');
            $table->integer('display_from_hours')->default(0)->after('display_from_days');
            $table->integer('display_from_minutes')->default(0)->after('display_from_hours');
            $table->boolean('loop')->default(true)->after('display_from_minutes');
            $table->boolean('link_open')->default(false)->after('loop');
            $table->string('show_on_display')->default('always')->after('link_open');
            $table->boolean('close_button')->default(true)->after('show_on_display');
            $table->boolean('hide_on_mobile')->default(false)->after('close_button');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn([
                'display_for',
                'display_last',
                'display_from_days',
                'display_from_hours',
                'display_from_minutes',
                'loop',
                'link_open',
                'show_on_display',
                'close_button',
                'hide_on_mobile'
            ]);
        });
    }
};
