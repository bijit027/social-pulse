<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Website;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function woocommerce(Request $request, string $pixelId)
    {
        \Log::info('WooCommerce Webhook RAW DATA:', $request->all());

        $website = Website::where('pixel_id', $pixelId)
            ->where('is_active', true)
            ->first();

        if (!$website) {
            return response()->json(['ok' => false], 404);
        }

        $data = $request->all();

        // Customer name
        $firstName = $data['billing']['first_name']
            ?? $data['shipping']['first_name']
            ?? null;

        $lastName = $data['billing']['last_name']
            ?? $data['shipping']['last_name']
            ?? null;

        // Location
        $city = $data['billing']['city']
            ?? $data['shipping']['city']
            ?? null;

        $countryCode = $data['billing']['country']
            ?? $data['shipping']['country']
            ?? null;

        $country = $this->getCountryName($countryCode ?? '');

        // Product details
        $productName  = null;
        $quantity     = 1;
        $productUrl   = null;
        $productImage = null;

        if (!empty($data['line_items']) && is_array($data['line_items'])) {
            $firstItem = $data['line_items'][0];

            $productName  = $firstItem['name']
                ?? $firstItem['product_name']
                ?? $firstItem['title']
                ?? null;

            $quantity = $firstItem['quantity'] ?? 1;

            // Product image
            $productImage = $firstItem['image']['src'] ?? null;

            // Build product URL from _links and product_id
            $selfHref  = $data['_links']['self'][0]['href'] ?? '';
            $scheme    = parse_url($selfHref, PHP_URL_SCHEME);
            $host      = parse_url($selfHref, PHP_URL_HOST);
            $siteUrl   = ($scheme && $host) ? $scheme . '://' . $host : null;

            if (!$siteUrl) {
                $domain  = $website->domain ?? '';
                $siteUrl = str_starts_with($domain, 'http')
                    ? $domain
                    : 'https://' . $domain;
            }

            if (isset($firstItem['product_id'])) {
                $productUrl = $siteUrl . '/?post_type=product&p=' 
                    . $firstItem['product_id'];
            }
        }

        // Build customer name
        $customerName = 'Someone';
        if ($firstName && $lastName) {
            $customerName = $firstName . ' ' . substr($lastName, 0, 1) . '.';
        } elseif ($firstName) {
            $customerName = $firstName;
        }

        // Build message
        if ($productName) {
            $message = $quantity > 1
                ? $customerName . ' just purchased ' . $quantity . 'x ' . $productName
                : $customerName . ' just purchased ' . $productName;
        } else {
            $message = $customerName . ' just made a purchase';
        }

        // Save notification
        Notification::create([
            'website_id'    => $website->id,
            'type'          => 'purchase',
            'message'       => $message,
            'city'          => $city,
            'country'       => $country,
            'emoji'         => $this->getEmoji($data),
            'product_url'   => $productUrl,
            'product_image' => $productImage,
            'is_active'     => true,
            'display_order' => 0,
            'source'        => 'woocommerce',
        ]);

        return response()->json(['ok' => true]);
    }

    public function stripe(Request $request, string $pixelId)
    {
        \Log::info('Stripe Webhook RAW DATA:', $request->all());

        $website = Website::where('pixel_id', $pixelId)
            ->where('is_active', true)
            ->first();

        if (!$website) {
            return response()->json(['ok' => false], 404);
        }

        $data = $request->all();

        // Stripe sends different event types
        // We only care about successful payments
        $eventType = $data['type'] ?? '';

        // Handle these Stripe events:
        // payment_intent.succeeded
        // checkout.session.completed
        // charge.succeeded

        if (!in_array($eventType, [
            'payment_intent.succeeded',
            'checkout.session.completed',
            'charge.succeeded',
        ])) {
            return response()->json(['ok' => true]);
        }

        $object = $data['data']['object'] ?? [];

        // Extract customer info based on event type
        $firstName   = null;
        $lastName    = null;
        $city        = null;
        $countryCode = null;
        $productName = null;
        $productUrl  = null;
        $amount      = null;

        if ($eventType === 'checkout.session.completed') {
            // Best event — has most data
            $firstName = $object['customer_details']['name'] 
                ? explode(' ', $object['customer_details']['name'])[0] 
                : null;

            $lastName = $object['customer_details']['name']
                ? implode(' ', array_slice(
                    explode(' ', $object['customer_details']['name']), 1
                  ))
                : null;

            $city        = $object['customer_details']['address']['city'] ?? null;
            $countryCode = $object['customer_details']['address']['country'] ?? null;
            $amount      = isset($object['amount_total']) 
                ? '$' . number_format($object['amount_total'] / 100, 2)
                : null;

            // Get product from line items if available
            $productName = $object['metadata']['product_name'] 
                ?? $object['description']
                ?? null;

        } elseif ($eventType === 'payment_intent.succeeded') {
            $firstName   = $object['metadata']['customer_name'] 
                ? explode(' ', $object['metadata']['customer_name'])[0]
                : null;
            $city        = $object['metadata']['city'] ?? null;
            $countryCode = $object['shipping']['address']['country'] 
                ?? $object['metadata']['country'] 
                ?? null;
            $productName = $object['metadata']['product_name']
                ?? $object['description']
                ?? null;
            $amount      = isset($object['amount'])
                ? '$' . number_format($object['amount'] / 100, 2)
                : null;

        } elseif ($eventType === 'charge.succeeded') {
            $billingDetails = $object['billing_details'] ?? [];
            $fullName       = $billingDetails['name'] ?? null;
            $firstName      = $fullName 
                ? explode(' ', $fullName)[0] 
                : null;
            $lastName       = $fullName
                ? implode(' ', array_slice(explode(' ', $fullName), 1))
                : null;
            $city        = $billingDetails['address']['city'] ?? null;
            $countryCode = $billingDetails['address']['country'] ?? null;
            $productName = $object['description'] ?? null;
            $amount      = isset($object['amount'])
                ? '$' . number_format($object['amount'] / 100, 2)
                : null;
        }

        // Build customer name
        $customerName = 'Someone';
        if ($firstName && $lastName) {
            $customerName = $firstName . ' ' . substr($lastName, 0, 1) . '.';
        } elseif ($firstName) {
            $customerName = $firstName;
        }

        // Build message
        if ($productName) {
            $message = $customerName . ' just purchased ' . $productName;
        } elseif ($amount) {
            $message = $customerName . ' just made a ' . $amount . ' purchase';
        } else {
            $message = $customerName . ' just made a purchase';
        }

        $country = $this->getCountryName($countryCode ?? '');

        Notification::create([
            'website_id'    => $website->id,
            'type'          => 'purchase',
            'message'       => $message,
            'city'          => $city,
            'country'       => $country,
            'emoji'         => '💳',
            'is_active'     => true,
            'display_order' => 0,
            'source'        => 'stripe',
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
