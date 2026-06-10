<?php
namespace App\Http\Controllers;

use App\Models\NotificationDisplay;
use App\Models\Website;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function serve(string $pixelId)
    {
        $website = Website::where('pixel_id', $pixelId)
            ->where('is_active', true)
            ->first();

        if (!$website) {
            return response()->json(['notifications' => [], 'display_settings' => []]);
        }

        // First try: get woocommerce notifications from last 24 hours
        $notifications = $website->notifications()
            ->where('is_active', true)
            ->where('source', 'woocommerce')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'type', 'message', 'city', 'country', 'emoji', 'created_at']);

        // Fallback: if no recent webhook notifications, show manual ones
        if ($notifications->isEmpty()) {
            $notifications = $website->notifications()
                ->where('is_active', true)
                ->where('source', 'manual')
                ->orderBy('display_order')
                ->get(['id', 'type', 'message', 'city', 'country', 'emoji', 'created_at']);
        }

        $displaySettings = [
            'display_for' => $website->display_for ?? 5,
            'display_last' => $website->display_last ?? 20,
            'display_from_days' => $website->display_from_days ?? 30,
            'display_from_hours' => $website->display_from_hours ?? 0,
            'display_from_minutes' => $website->display_from_minutes ?? 0,
            'loop' => $website->loop ?? true,
            'link_open' => $website->link_open ?? false,
            'show_on_display' => $website->show_on_display ?? 'always',
            'close_button' => $website->close_button ?? true,
            'hide_on_mobile' => $website->hide_on_mobile ?? false,
        ];

        return response()->json(['notifications' => $notifications, 'display_settings' => $displaySettings]);
    }

    public function trackDisplay(Request $request, string $pixelId)
    {
        $website = Website::where('pixel_id', $pixelId)->first();
        if (!$website) return response()->json(['ok' => true]);

        NotificationDisplay::create([
            'website_id'      => $website->id,
            'notification_id' => $request->notification_id,
            'visitor_ip'      => $request->ip(),
            'displayed_at'    => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
