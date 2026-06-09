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
            return response()->json(['notifications' => []]);
        }

        $notifications = $website->notifications()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'type', 'message', 'city', 'country', 'emoji']);

        return response()->json(['notifications' => $notifications]);
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
