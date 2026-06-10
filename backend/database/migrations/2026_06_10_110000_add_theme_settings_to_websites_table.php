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
            $table->string('theme')->default('light')->after('hide_on_mobile');
            $table->string('image_shape')->default('rounded')->after('theme');
            $table->string('widget_position')->default('bottom-right')->after('image_shape');
            $table->string('background_color')->default('#ffffff')->after('widget_position');
            $table->string('text_color')->default('#1a1a1a')->after('background_color');
            $table->string('accent_color')->default('#FF6B35')->after('text_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn([
                'theme',
                'image_shape',
                'widget_position',
                'background_color',
                'text_color',
                'accent_color'
            ]);
        });
    }
};
