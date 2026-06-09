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

        if (!$user->canAddWebsite()) {
            return response()->json([
                'message' => 'You have reached your website limit. Please upgrade.'
            ], 403);
        }

        $website = $user->websites()->create($request->validated());
        return response()->json($website, 201);
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
}
