<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Website;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function woocommerce(Request $request, string $pixelId)
    {
        $website = Website::where('pixel_id', $pixelId)
            ->where('is_active', true)
            ->first();

        if (!$website) {
            return response()->json(['ok' => false], 404);
        }

        $data = $request->all();

        // Get customer info
        $firstName  = $data['billing']['first_name'] ?? 'Someone';
        $city       = $data['billing']['city'] ?? null;
        $country    = $data['billing']['country'] ?? null;

        // Get product name
        $productName = 'a product';
        if (!empty($data['line_items'])) {
            $productName = $data['line_items'][0]['name'] ?? 'a product';
        }

        // Build message
        $message = $firstName . ' just purchased ' . $productName;

        // Save as notification
        Notification::create([
            'website_id'    => $website->id,
            'type'          => 'purchase',
            'message'       => $message,
            'city'          => $city,
            'country'       => $country,
            'emoji'         => '🛒',
            'is_active'     => true,
            'display_order' => 0,
        ]);

        return response()->json(['ok' => true]);
    }
}
