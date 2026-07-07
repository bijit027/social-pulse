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

        // Get all active notifications (both automated and manual)
        $notifications = $website->notifications()
            ->where('is_active', true)
            // Prioritize manually ordered ones, then newest automated ones
            ->orderBy('display_order')
            ->orderBy('created_at', 'desc')
            ->limit($website->display_last ?? 20)
            ->get(['id', 'type', 'message', 'city', 'country', 'emoji', 'created_at', 'product_url', 'rating', 'button_text', 'source']);

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

        $themeSettings = [
            'theme' => $website->theme ?? 'light',
            'image_shape' => $website->image_shape ?? 'rounded',
            'widget_position' => $website->widget_position ?? 'bottom-right',
            'background_color' => $website->background_color ?? '#ffffff',
            'text_color' => $website->text_color ?? '#1a1a1a',
            'accent_color' => $website->accent_color ?? '#FF6B35',
            'custom_css' => $website->custom_css ?? '',
        ];

        return response()->json([
            'notifications' => $notifications, 
            'display_settings' => $displaySettings, 
            'theme_settings' => $themeSettings
        ]);
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
