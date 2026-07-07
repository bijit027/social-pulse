<?php

namespace App\Http\Controllers;

use App\Models\VisitorSession;
use App\Models\Website;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    // Known bot user agents to ignore
    private array $botAgents = [
        'googlebot', 'bingbot', 'slurp', 'duckduckbot',
        'baiduspider', 'yandexbot', 'sogou', 'exabot',
        'facebot', 'ia_archiver', 'python-requests',
        'curl', 'wget', 'scrapy', 'ahrefsbot',
        'semrushbot', 'mj12bot', 'dotbot', 'rogerbot',
        'uptimerobot', 'pingdom', 'gtmetrix',
    ];

    public function ping(Request $request, string $pixelId)
    {
        // Check for bots
        $userAgent = strtolower(
            $request->userAgent() ?? ''
        );
        
        foreach ($this->botAgents as $bot) {
            if (str_contains($userAgent, $bot)) {
                return response()->json(['visitors' => 0]);
            }
        }

        $website = Website::where('pixel_id', $pixelId)
            ->where('is_active', true)
            ->first();

        if (!$website) {
            return response()->json(['visitors' => 0]);
        }

        // Create anonymous session hash
        // Hash IP + UserAgent so no personal data stored
        $sessionHash = hash('sha256', 
            $request->ip() . 
            $request->userAgent() . 
            date('Y-m-d') // changes daily for privacy
        );

        $pageUrl = $request->input('page_url', '/');
        
        // Normalize URL - remove query strings for accuracy
        $pageUrl = strtok($pageUrl, '?');
        $pageUrl = substr($pageUrl, 0, 500); // max length

        // Upsert visitor session
        VisitorSession::updateOrCreate(
            [
                'website_id'   => $website->id,
                'session_hash' => $sessionHash,
                'page_url'     => $pageUrl,
            ],
            [
                'visitor_ip'   => null, // GDPR: don't store raw IP
                'last_seen_at' => now(),
            ]
        );

        // Clean up old sessions (older than 10 minutes)
        // Only run 10% of requests to avoid overhead
        if (rand(1, 10) === 1) {
            VisitorSession::where('website_id', $website->id)
                ->where('last_seen_at', '<', now()->subMinutes(10))
                ->delete();
        }

        // Get counts
        $pageVisitors = VisitorSession::getActiveCount(
            $website->id, 
            $pageUrl,
            5 // last 5 minutes
        );

        $totalVisitors = VisitorSession::getActiveCount(
            $website->id,
            null,
            5
        );

        // Broadcast the update to the dashboard via WebSockets
        broadcast(new \App\Events\ActiveVisitorsUpdated($website->id, $totalVisitors));

        return response()->json([
            'page_visitors'  => $pageVisitors,
            'total_visitors' => $totalVisitors,
        ]);
    }
}
