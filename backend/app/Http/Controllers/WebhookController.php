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
        $firstName   = $data['billing']['first_name'] ?? null;
        $lastName    = $data['billing']['last_name'] ?? null;
        $city        = $data['billing']['city'] ?? null;
        $country     = $data['billing']['country'] ?? null;

        // Build customer name
        $customerName = 'Someone';
        if ($firstName) {
            $customerName = $firstName;
            if ($lastName) {
                $customerName .= ' ' . substr($lastName, 0, 1) . '.';
            }
        }

        // Get product name from first line item
        $productName = 'a product';
        if (!empty($data['line_items']) && is_array($data['line_items'])) {
            $productName = $data['line_items'][0]['name'] 
                ?? $data['line_items'][0]['product_name'] 
                ?? 'a product';
        }

        // Build natural message
        $message = $customerName . ' just purchased ' . $productName;

        // Save as notification with source = woocommerce
        Notification::create([
            'website_id'    => $website->id,
            'type'          => 'purchase',
            'message'       => $message,
            'city'          => $city,
            'country'       => $country,
            'emoji'         => '🛒',
            'is_active'     => true,
            'display_order' => 0,
            'source'        => 'woocommerce',
        ]);

        return response()->json(['ok' => true]);
    }
}
