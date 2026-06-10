<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Website;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function woocommerce(Request $request, string $pixelId)
    {
        // Log everything WooCommerce sends
        \Log::info('WooCommerce Webhook RAW DATA:', $request->all());

        $website = Website::where('pixel_id', $pixelId)
            ->where('is_active', true)
            ->first();

        if (!$website) {
            return response()->json(['ok' => false], 404);
        }

        $data = $request->all();

        // Try multiple possible field locations for customer name
        $firstName = $data['billing']['first_name']
            ?? $data['shipping']['first_name']
            ?? $data['customer']['first_name']
            ?? null;

        $lastName = $data['billing']['last_name']
            ?? $data['shipping']['last_name']
            ?? $data['customer']['last_name']
            ?? null;

        // Try multiple possible field locations for city/country
        $city = $data['billing']['city']
            ?? $data['shipping']['city']
            ?? null;

        $countryCode = $data['billing']['country']
            ?? $data['shipping']['country']
            ?? null;

        // Convert country code to full name
        $country = $this->getCountryName($countryCode ?? '');

        // Try multiple possible field locations for product name
        $productName = null;
        $quantity = 1;

        if (!empty($data['line_items']) && is_array($data['line_items'])) {
            $firstItem = $data['line_items'][0];
            $productName = $firstItem['name']
                ?? $firstItem['product_name']
                ?? $firstItem['title']
                ?? null;
            $quantity = $firstItem['quantity'] ?? 1;
        }

        // Build customer display name
        $customerName = 'Someone';
        if ($firstName && $lastName) {
            // Show first name + last initial for privacy
            $customerName = $firstName . ' ' . substr($lastName, 0, 1) . '.';
        } elseif ($firstName) {
            $customerName = $firstName;
        }

        // Build final message
        if ($productName) {
            if ($quantity > 1) {
                $message = $customerName . ' just purchased ' . $quantity . 'x ' . $productName;
            } else {
                $message = $customerName . ' just purchased ' . $productName;
            }
        } else {
            $message = $customerName . ' just made a purchase';
        }

        // Build emoji based on product
        $emoji = $this->getEmoji($data);

        // Save notification
        Notification::create([
            'website_id'    => $website->id,
            'type'          => 'purchase',
            'message'       => $message,
            'city'          => $city,
            'country'       => $country,
            'emoji'         => $emoji,
            'is_active'     => true,
            'display_order' => 0,
            'source'        => 'woocommerce',
        ]);

        return response()->json(['ok' => true]);
    }

    private function getCountryName(string $code): string
    {
        $countries = [
            'BD' => 'Bangladesh',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'IN' => 'India',
            'AU' => 'Australia',
            'CA' => 'Canada',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'JP' => 'Japan',
            'CN' => 'China',
            'BR' => 'Brazil',
            'MX' => 'Mexico',
            'SG' => 'Singapore',
            'MY' => 'Malaysia',
            'PK' => 'Pakistan',
            'NL' => 'Netherlands',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'NZ' => 'New Zealand',
            'ZA' => 'South Africa',
            'AE' => 'UAE',
            'SA' => 'Saudi Arabia',
        ];

        return $countries[strtoupper($code)] ?? $code;
    }

    private function getEmoji(array $data): string
    {
        $productName = strtolower(
            $data['line_items'][0]['name'] ?? ''
        );

        if (str_contains($productName, 'plan') || 
            str_contains($productName, 'subscription')) {
            return '⚡';
        }
        if (str_contains($productName, 'course') || 
            str_contains($productName, 'training')) {
            return '📚';
        }
        if (str_contains($productName, 'shirt') || 
            str_contains($productName, 'clothing')) {
            return '👕';
        }
        if (str_contains($productName, 'book')) {
            return '📖';
        }

        return '🛒'; // default
    }
}
