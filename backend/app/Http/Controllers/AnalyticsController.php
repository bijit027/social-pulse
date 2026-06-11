<?php

namespace App\Http\Controllers;

use App\Models\NotificationAnalytics;
use App\Models\Notification;
use App\Models\Website;
use App\Models\VisitorSession;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

    /**
     * Get comprehensive analytics data for the analytics dashboard
     */
    public function getAnalytics(Request $request)
    {
        try {
            $user = auth()->user();
            $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
            $endDate = $request->query('end_date', now()->toDateString());
            $period = $request->query('period', '30d');
            $siteId = $request->query('site_id');

            // Get user's websites
            $websitesQuery = Website::where('user_id', $user->id);
            if ($siteId) {
                $websitesQuery->where('id', $siteId);
            }
            $websites = $websitesQuery->get();
            $websiteIds = $websites->pluck('id');

            if ($websiteIds->isEmpty()) {
                return response()->json([
                    'summary' => [
                        'total_views' => 0,
                        'total_clicks' => 0,
                        'ctr' => 0,
                        'total_displays' => 0
                    ],
                    'views_change' => 0,
                    'clicks_change' => 0,
                    'ctr_change' => 0,
                    'displays_change' => 0,
                    'dates' => [],
                    'views' => [],
                    'clicks' => [],
                    'source_distribution' => [],
                    'top_notifications' => [],
                    'site_performance' => [],
                    'notifications' => []
                ]);
            }

            // Calculate previous period for change percentages
            $days = $this->getDaysFromPeriod($period);
            $previousStartDate = Carbon::parse($startDate)->subDays($days)->toDateString();
            $previousEndDate = Carbon::parse($startDate)->subDay()->toDateString();

            // Get current period stats
            $currentStats = $this->getPeriodStats($websiteIds, $startDate, $endDate);
            $previousStats = $this->getPeriodStats($websiteIds, $previousStartDate, $previousEndDate);

            // Calculate changes
            $viewsChange = $this->calculateChange($currentStats['total_views'], $previousStats['total_views']);
            $clicksChange = $this->calculateChange($currentStats['total_clicks'], $previousStats['total_clicks']);
            $ctrChange = $this->calculateChange($currentStats['ctr'], $previousStats['ctr']);
            $displaysChange = $this->calculateChange($currentStats['total_displays'], $previousStats['total_displays']);

            // Get daily data for charts
            $dailyData = $this->getDailyData($websiteIds, $startDate, $endDate);

            // Get source distribution
            $sourceDistribution = $this->getSourceDistribution($websiteIds, $startDate, $endDate);

            // Get top performing notifications
            $topNotifications = $this->getTopNotifications($websiteIds, $startDate, $endDate);

            // Get site performance
            $sitePerformance = $this->getSitePerformance($websiteIds, $startDate, $endDate);

            // Get all notifications for source distribution calculation
            $notifications = Notification::whereIn('website_id', $websiteIds)->get();

            return response()->json([
                'summary' => [
                    'total_views' => $currentStats['total_views'],
                    'total_clicks' => $currentStats['total_clicks'],
                    'ctr' => $currentStats['ctr'],
                    'total_displays' => $currentStats['total_displays']
                ],
                'views_change' => $viewsChange,
                'clicks_change' => $clicksChange,
                'ctr_change' => $ctrChange,
                'displays_change' => $displaysChange,
                'dates' => $dailyData['dates'],
                'views' => $dailyData['views'],
                'clicks' => $dailyData['clicks'],
                'source_distribution' => $sourceDistribution,
                'top_notifications' => $topNotifications,
                'site_performance' => $sitePerformance,
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            \Log::error('Analytics fetch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get stats for a specific period
     */
    private function getPeriodStats($websiteIds, $startDate, $endDate)
    {
        $analytics = NotificationAnalytics::whereHas('notification', function ($query) use ($websiteIds) {
            $query->whereIn('website_id', $websiteIds);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->selectRaw('SUM(views) as total_views, SUM(clicks) as total_clicks')
        ->first();

        $totalViews = $analytics->total_views ?? 0;
        $totalClicks = $analytics->total_clicks ?? 0;
        $ctr = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0;

        // Get total displays from notifications (count of notifications as displays)
        $totalDisplays = Notification::whereIn('website_id', $websiteIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return [
            'total_views' => $totalViews,
            'total_clicks' => $totalClicks,
            'ctr' => $ctr,
            'total_displays' => $totalDisplays
        ];
    }

    /**
     * Get daily data for charts
     */
    private function getDailyData($websiteIds, $startDate, $endDate)
    {
        $dailyStats = NotificationAnalytics::whereHas('notification', function ($query) use ($websiteIds) {
            $query->whereIn('website_id', $websiteIds);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->selectRaw('created_at, SUM(views) as views, SUM(clicks) as clicks')
        ->groupBy('created_at')
        ->orderBy('created_at')
        ->get();

        $dates = [];
        $views = [];
        $clicks = [];

        // Fill in missing dates with zeros
        $period = Carbon::parse($startDate)->daysUntil(Carbon::parse($endDate));
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $dates[] = $dateStr;
            
            $dayData = $dailyStats->firstWhere('created_at', $dateStr);
            $views[] = $dayData->views ?? 0;
            $clicks[] = $dayData->clicks ?? 0;
        }

        return [
            'dates' => $dates,
            'views' => $views,
            'clicks' => $clicks
        ];
    }

    /**
     * Get source distribution
     */
    private function getSourceDistribution($websiteIds, $startDate, $endDate)
    {
        $notifications = Notification::whereIn('website_id', $websiteIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->get();

        $total = $notifications->sum('count');
        if ($total === 0) {
            return [];
        }

        return $notifications->map(function ($item) use ($total) {
            return [
                'name' => $this->getSourceLabel($item->source),
                'value' => $item->count,
                'count' => $item->count
            ];
        })->toArray();
    }

    /**
     * Get top performing notifications
     */
    private function getTopNotifications($websiteIds, $startDate, $endDate, $limit = 10)
    {
        return Notification::whereIn('website_id', $websiteIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['website:id,name'])
            ->select('id', 'message', 'source', 'city', 'country', 'website_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notification) use ($startDate, $endDate) {
                // Get analytics for this notification
                $analytics = NotificationAnalytics::where('notification_id', $notification->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->selectRaw('SUM(views) as total_views, SUM(clicks) as total_clicks')
                    ->first();

                return [
                    'id' => $notification->id,
                    'message' => $notification->message,
                    'source' => $notification->source,
                    'city' => $notification->city,
                    'country' => $notification->country,
                    'website' => [
                        'id' => $notification->website->id,
                        'name' => $notification->website->name
                    ],
                    'total_views' => $analytics->total_views ?? 0,
                    'total_clicks' => $analytics->total_clicks ?? 0,
                    'created_at' => $notification->created_at
                ];
            })->toArray();
    }

    /**
     * Get site performance
     */
    private function getSitePerformance($websiteIds, $startDate, $endDate)
    {
        return Website::whereIn('id', $websiteIds)
            ->withCount(['notifications' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function ($website) use ($startDate, $endDate) {
                // Get analytics for this website
                $analytics = NotificationAnalytics::whereHas('notification', function ($query) use ($website) {
                    $query->where('website_id', $website->id);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('SUM(views) as total_views, SUM(clicks) as total_clicks')
                ->first();

                // Get total displays
                $totalDisplays = Notification::where('website_id', $website->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                // Get active visitors
                $activeVisitors = VisitorSession::getActiveCount($website->id, null, 5);

                return [
                    'id' => $website->id,
                    'name' => $website->name,
                    'domain' => $website->domain,
                    'total_views' => $analytics->total_views ?? 0,
                    'total_clicks' => $analytics->total_clicks ?? 0,
                    'total_displays' => $totalDisplays,
                    'active_visitors' => $activeVisitors
                ];
            })->toArray();
    }

    /**
     * Calculate percentage change
     */
    private function calculateChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get number of days from period string
     */
    private function getDaysFromPeriod($period)
    {
        return match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30
        };
    }

    /**
     * Get human-readable source label
     */
    private function getSourceLabel($source)
    {
        return match($source) {
            'woocommerce' => 'WooCommerce',
            'stripe' => 'Stripe',
            'surecart' => 'SureCart',
            'edd' => 'Easy Digital Downloads',
            'manual' => 'Manual',
            default => ucfirst($source)
        };
    }
}
