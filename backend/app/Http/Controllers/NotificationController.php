<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationRequest;
use App\Models\Notification;
use App\Models\Website;

class NotificationController extends Controller
{
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
