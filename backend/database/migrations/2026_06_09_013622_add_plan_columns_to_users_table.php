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
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan')->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('lemon_squeezy_customer_id')->nullable();
            $table->string('lemon_squeezy_subscription_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan', 'trial_ends_at', 'lemon_squeezy_customer_id', 'lemon_squeezy_subscription_id']);
        });
    }
};
