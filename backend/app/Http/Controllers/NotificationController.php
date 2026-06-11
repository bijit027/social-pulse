<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationRequest;
use App\Models\Notification;
use App\Models\Website;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the current user (global notifications page)
     */
    public function getAll(Request $request)
    {
        $user = auth()->user();
        
        $query = Notification::whereHas('website', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['website:id,name']);

        // Apply filters
        if ($request->has('source') && $request->source) {
            $query->where('source', $request->source);
        }
        
        if ($request->has('status') && $request->status) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        if ($request->has('site_id') && $request->site_id) {
            $query->where('website_id', $request->site_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $search = $request->search;
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);
        
        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $notifications->items(),
            'total' => $notifications->total(),
            'per_page' => $notifications->perPage(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage()
        ]);
    }

    /**
     * Create a manual notification (global, not tied to specific website in URL)
     */
    public function createGlobal(Request $request)
    {
        $request->validate([
            'website_id' => 'required|exists:websites,id',
            'message' => 'required|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'emoji' => 'nullable|string|max:10'
        ]);

        $website = Website::findOrFail($request->website_id);
        
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notification = Notification::create([
            'website_id' => $website->id,
            'message' => $request->message,
            'city' => $request->city,
            'country' => $request->country,
            'emoji' => $request->emoji ?? '🛒',
            'source' => 'manual',
            'is_active' => true,
            'total_displays' => 0
        ]);

        return response()->json($notification, 201);
    }

    public function index(Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($website->notifications()->orderBy('display_order')->get());
    }

    public function store(StoreNotificationRequest $request, Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = array_merge($request->validated(), [
            'source' => 'manual',
            'emoji'  => $request->input('emoji', '🛒'),
        ]);
        $notification = $website->notifications()->create($data);
        return response()->json($notification, 201);
    }

    public function update(StoreNotificationRequest $request, Notification $notification)
    {
        if ($notification->website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notification->update($request->validated());
        return response()->json($notification);
    }

    public function destroy(Notification $notification)
    {
        if ($notification->website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notification->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function toggle(Notification $notification)
    {
        if ($notification->website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notification->update(['is_active' => !$notification->is_active]);
        return response()->json($notification);
    }
}
