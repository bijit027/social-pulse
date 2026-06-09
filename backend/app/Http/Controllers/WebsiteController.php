<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreWebsiteRequest;
use App\Models\Website;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function index()
    {
        $websites = auth()->user()->websites()->withCount('notifications')->get();
        return response()->json($websites);
    }

    public function store(StoreWebsiteRequest $request)
    {
        $user = auth()->user();

        $website = $user->websites()->create($request->validated());
        return response()->json($website, 201);
    }

    public function show(Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $website->load('notifications');
        return response()->json($website);
    }

    public function update(StoreWebsiteRequest $request, Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $website->update($request->validated());
        return response()->json($website);
    }

    public function destroy(Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $website->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function snippet(Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $snippet = '<script src="' . config('app.url') . '/widget.js" data-pixel-id="' . $website->pixel_id . '"></script>';

        return response()->json(['snippet' => $snippet]);
    }

    public function analytics(Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notifications = $website->notifications()
            ->withCount('displays')
            ->with(['displays' => function($query) {
                $query->latest('displayed_at')->limit(1);
            }])
            ->get()
            ->map(function($notification) {
                return [
                    'id'             => $notification->id,
                    'message'        => $notification->message,
                    'type'           => $notification->type,
                    'emoji'          => $notification->emoji,
                    'is_active'      => $notification->is_active,
                    'total_displays' => $notification->displays_count,
                    'last_shown'     => $notification->displays->first()?->displayed_at,
                ];
            });

        $totalDisplays = $website->notificationDisplays()->count();
        $thisWeek = $website->notificationDisplays()
            ->where('displayed_at', '>=', now()->subDays(7))
            ->count();
        $today = $website->notificationDisplays()
            ->where('displayed_at', '>=', now()->startOfDay())
            ->count();

        return response()->json([
            'total_displays'     => $totalDisplays,
            'displays_this_week' => $thisWeek,
            'displays_today'     => $today,
            'notifications'      => $notifications,
        ]);
    }
}
