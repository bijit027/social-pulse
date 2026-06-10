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
        Schema::create('visitor_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('page_url', 500);
            $table->string('session_hash', 64); // hashed IP+UserAgent
            $table->string('visitor_ip', 45)->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['website_id', 'last_seen_at']);
            $table->index(['website_id', 'page_url', 'last_seen_at']);
            $table->unique(['website_id', 'session_hash', 'page_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_sessions');
    }
};
