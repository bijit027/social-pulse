<?php

namespace App\Console\Commands;

use App\Models\VisitorSession;
use Illuminate\Console\Command;

class CleanVisitorSessions extends Command
{
    protected $signature   = 'visitors:clean';
    protected $description = 'Clean up old visitor sessions';

    public function handle()
    {
        $deleted = VisitorSession::where(
            'last_seen_at', '<', now()->subHours(1)
        )->delete();

        $this->info("Deleted {$deleted} old visitor sessions.");
    }
}
