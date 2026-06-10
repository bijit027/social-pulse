<?php

namespace App\Http\Controllers;

use App\Models\NotificationAnalytics;
use App\Models\Website;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function track(Request $request)
    {
        try {
            $request->validate([
                'notification_id' => 'required|integer',
                'type' => 'required|in:clicks,views'
            ]);

            // Check if analytics should be counted (bot detection)
            if (!NotificationAnalytics::shouldCount()) {
                \Log::info('Analytics blocked by bot detection', [
                    'notification_id' => $request->notification_id,
                    'type' => $request->type,
                    'user_agent' => $request->userAgent()
                ]);
                return response()->json(['success' => true]);
            }

            NotificationAnalytics::track($request->notification_id, $request->type);

            \Log::info('Analytics tracked successfully', [
                'notification_id' => $request->notification_id,
                'type' => $request->type
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Analytics tracking failed', [
                'error' => $e->getMessage(),
                'notification_id' => $request->notification_id,
                'type' => $request->type
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getStats(Request $request, $websiteId)
    {
        $website = Website::findOrFail($websiteId);
        
        $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $stats = NotificationAnalytics::whereHas('notification', function ($query) use ($website) {
            $query->where('website_id', $website->id);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->selectRaw('SUM(views) as total_views, SUM(clicks) as total_clicks, created_at')
        ->groupBy('created_at')
        ->orderBy('created_at')
        ->get();

        $totalViews = $stats->sum('total_views');
        $totalClicks = $stats->sum('total_clicks');
        $ctr = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0;

        return response()->json([
            'stats' => $stats,
            'summary' => [
                'total_views' => $totalViews,
                'total_clicks' => $totalClicks,
                'ctr' => $ctr
            ]
        ]);
    }
}
