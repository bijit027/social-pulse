Complete Instructions — Fix WooCommerce Customer Name & Product

THE PROBLEM
WooCommerce sends order data as a webhook but the field
structure is different from what we expected. We need to
log the exact data first, then parse it correctly.

TASK 1 — Add Logging to See Real Data
File: backend/app/Http/Controllers/WebhookController.php
Replace the entire woocommerce method with this:
phppublic function woocommerce(Request $request, string $pixelId)
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

    $country = $data['billing']['country']
        ?? $data['shipping']['country']
        ?? null;

    // Try multiple possible field locations for product name
    $productName = null;

    if (!empty($data['line_items']) && is_array($data['line_items'])) {
        $firstItem = $data['line_items'][0];
        $productName = $firstItem['name']
            ?? $firstItem['product_name']
            ?? $firstItem['title']
            ?? null;
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
        $message = $customerName . ' just purchased ' . $productName;
    } else {
        $message = $customerName . ' just made a purchase';
    }

    // Build emoji based on order
    $emoji = '🛒';

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

TASK 2 — Check the Log After Placing Order
After updating the controller, place a new test order
on WooCommerce. Then run in terminal:
bashcd /Users/bijitdeb/Projects/Laravel/SocialPulse/backend
tail -200 storage/logs/laravel.log | grep -A 100 "WooCommerce Webhook RAW DATA"
This shows exactly what fields WooCommerce is sending.
The log will reveal the real field names.

TASK 3 — Common WooCommerce Field Structures
WooCommerce REST API v3 sends data in these formats.
Make sure the controller handles ALL of these:
php// Format 1 — Standard WooCommerce webhook
$data['billing']['first_name']     // customer first name
$data['billing']['last_name']      // customer last name
$data['billing']['city']           // customer city
$data['billing']['country']        // country code e.g. "US", "BD"
$data['line_items'][0]['name']     // product name
$data['line_items'][0]['quantity'] // quantity ordered
$data['total']                     // order total

// Format 2 — Sometimes nested differently
$data['billing_first_name']
$data['billing_last_name']
$data['billing_city']
$data['billing_country']

// Format 3 — Customer object
$data['customer']['first_name']
$data['customer']['billing_first_name']

TASK 4 — Improve the Message Format
Once we know the real field names, format the message
to look natural and convincing:
Good examples:
"Sarah J. just purchased Blue T-Shirt"
"Ahmed from Dhaka just bought Premium Plan ⚡"
"Someone from London just purchased 2x Running Shoes"
Rules for message building:
php// If quantity > 1, show quantity
if ($quantity > 1) {
    $message = $customerName . ' just purchased ' . $quantity . 'x ' . $productName;
} else {
    $message = $customerName . ' just purchased ' . $productName;
}

// If city exists, add location feel
// (city is stored separately, shown by widget automatically)

TASK 5 — Handle Country Code to Full Name
WooCommerce sends country as a 2-letter code like "BD", "US", "GB".
Convert it to full name for better display:
Add this helper method to WebhookController:
phpprivate function getCountryName(string $code): string
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
Then use it when saving:
php$country = $this->getCountryName(
    $data['billing']['country'] ?? ''
);

TASK 6 — Auto-select Emoji Based on Product or Type
phpprivate function getEmoji(array $data): string
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

SUMMARY OF CHANGES
File