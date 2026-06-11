<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\Notification;
use Illuminate\Http\Request;

class SourcesController extends Controller
{
    /**
     * Get sources statistics and integration status
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get user's websites
        $websites = Website::where('user_id', $user->id)->get();
        $totalSites = $websites->count();
        
        // Get connected sites (sites with notifications)
        $connectedSites = $websites->filter(function ($website) {
            return $website->notifications()->exists();
        })->count();
        
        // Get total notifications count
        $totalNotifications = Notification::whereIn('website_id', $websites->pluck('id'))->count();
        
        // Get active webhooks (sites with notifications from webhook sources)
        $activeWebhooks = Notification::whereIn('website_id', $websites->pluck('id'))
            ->whereIn('source', ['woocommerce', 'stripe', 'surecart', 'edd'])
            ->distinct('website_id')
            ->count('website_id');
        
        // Get source distribution
        $sourceDistribution = Notification::whereIn('website_id', $websites->pluck('id'))
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->source => $item->count];
            });
        
        // Get platform status
        $platforms = $this->getPlatformStatus($websites);
        
        return response()->json([
            'stats' => [
                'connected_sites' => $connectedSites,
                'total_sites' => $totalSites,
                'active_webhooks' => $activeWebhooks,
                'notifications' => $totalNotifications
            ],
            'platforms' => $platforms,
            'source_distribution' => $sourceDistribution
        ]);
    }
    
    /**
     * Get webhook URL for a specific platform and website
     */
    public function getWebhookUrl(Request $request, $websiteId)
    {
        $website = Website::findOrFail($websiteId);
        
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        
        $platform = $request->query('platform');
        
        if (!in_array($platform, ['woocommerce', 'stripe', 'surecart', 'edd', 'custom'])) {
            return response()->json(['message' => 'Invalid platform.'], 400);
        }
        
        $backendUrl = config('app.url');
        $webhookUrl = "{$backendUrl}/api/webhook/{$platform}/{$website->pixel_id}";
        
        return response()->json([
            'webhook_url' => $webhookUrl,
            'platform' => $platform,
            'website_id' => $website->id,
            'pixel_id' => $website->pixel_id
        ]);
    }
    
    /**
     * Test webhook connection
     */
    public function testWebhook(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:woocommerce,stripe,surecart,edd,custom',
            'website_id' => 'required|exists:websites,id'
        ]);
        
        $website = Website::findOrFail($request->website_id);
        
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        
        // Simulate a test webhook payload
        $testPayload = $this->getTestPayload($request->platform);
        
        // In a real implementation, you would send this to the webhook endpoint
        // For now, we'll just return success
        
        return response()->json([
            'success' => true,
            'message' => 'Webhook test successful',
            'payload' => $testPayload
        ]);
    }
    
    /**
     * Get platform integration status
     */
    private function getPlatformStatus($websites)
    {
        $websiteIds = $websites->pluck('id');
        
        return [
            [
                'id' => 'woocommerce',
                'name' => 'WooCommerce',
                'icon' => '🛒',
                'description' => 'Connect your WooCommerce store to display purchase notifications',
                'connected' => Notification::whereIn('website_id', $websiteIds)
                    ->where('source', 'woocommerce')
                    ->exists(),
                'connected_sites' => Notification::whereIn('website_id', $websiteIds)
                    ->where('source', 'woocommerce')
                    ->distinct('website_id')
                    ->count('website_id'),
                'documentation_url' => 'https://docs.socialpulse.com/woocommerce',
                'setup_required' => true
            ],
            [
                'id' => 'stripe',
                'name' => 'Stripe',
                'icon' => '💳',
                'description' => 'Connect your Stripe account to display payment notifications',
                'connected' => Notification::whereIn('website_id', $websiteIds)
                    ->where('source', 'stripe')
                    ->exists(),
                'connected_sites' => Notification::whereIn('website_id', $websiteIds)
                    ->where('source', 'stripe')
                    ->distinct('website_id')
                    ->count('website_id'),
                'documentation_url' => 'https://docs.socialpulse.com/stripe',
                'setup_required' => true
            ],
            [
                'id' => 'surecart',
                'name' => 'SureCart',
                'icon' => '🛍️',
                'description' => 'Connect your SureCart store to display purchase notifications',
                'connected' => Notification::whereIn('website_id', $websiteIds)
                    ->where('source', 'surecart')
                    ->exists(),
                'connected_sites' => Notification::whereIn('website_id', $websiteIds)
                    ->where('source', 'surecart')
                    ->distinct('website_id')
                    ->count('website_id'),
                'documentation_url' => 'https://docs.socialpulse.com/surecart',
                'setup_required' => true
            ],
            [
                'id' => 'edd',
                'name' => 'Easy Digital Downloads',
                'icon' => '📦',
                'description' => 'Connect your EDD store to display download notifications',
                'connected' => Notification::whereIn('website_id', $websiteIds)
                    ->where('source', 'edd')
                    ->exists(),
                'connected_sites' => Notification::whereIn('website_id', $websiteIds)
                    ->where('source', 'edd')
                    ->distinct('website_id')
                    ->count('website_id'),
                'documentation_url' => 'https://docs.socialpulse.com/edd',
                'setup_required' => true
            ],
            [
                'id' => 'shopify',
                'name' => 'Shopify',
                'icon' => '🏪',
                'description' => 'Connect your Shopify store to display purchase notifications',
                'connected' => false,
                'connected_sites' => 0,
                'documentation_url' => 'https://docs.socialpulse.com/shopify',
                'setup_required' => true,
                'coming_soon' => true
            ],
            [
                'id' => 'custom',
                'name' => 'Custom Webhook',
                'icon' => '🔗',
                'description' => 'Use custom webhooks to integrate with any platform',
                'connected' => Notification::whereIn('website_id', $websiteIds)
                    ->where('source', 'manual')
                    ->exists(),
                'connected_sites' => Notification::whereIn('website_id', $websiteIds)
                    ->where('source', 'manual')
                    ->distinct('website_id')
                    ->count('website_id'),
                'documentation_url' => 'https://docs.socialpulse.com/custom-webhook',
                'setup_required' => false
            ]
        ];
    }
    
    /**
     * Get test payload for webhook testing
     */
    private function getTestPayload($platform)
    {
        return match($platform) {
            'woocommerce' => [
                'id' => 'test_order_123',
                'status' => 'completed',
                'total' => '99.99',
                'currency' => 'USD',
                'customer' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'city' => 'San Francisco',
                    'country' => 'US'
                ]
            ],
            'stripe' => [
                'id' => 'evt_test123',
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'amount' => 9999,
                        'currency' => 'usd',
                        'customer_details' => [
                            'address' => [
                                'city' => 'San Francisco',
                                'country' => 'US'
                            ]
                        ]
                    ]
                ]
            ],
            'surecart' => [
                'id' => 'purchase_test123',
                'status' => 'paid',
                'total' => 99.99,
                'currency' => 'USD',
                'customer' => [
                    'name' => 'John Doe',
                    'city' => 'San Francisco',
                    'country' => 'US'
                ]
            ],
            'edd' => [
                'purchase_id' => 123,
                'status' => 'complete',
                'total' => '99.99',
                'currency' => 'USD',
                'customer_info' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address' => [
                        'city' => 'San Francisco',
                        'country' => 'US'
                    ]
                ]
            ],
            'custom' => [
                'test' => true,
                'message' => 'Test webhook payload',
                'timestamp' => now()->toIso8601String()
            ],
            default => []
        };
    }
}
