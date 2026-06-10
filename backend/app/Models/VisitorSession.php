<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorSession extends Model
{
    protected $fillable = [
        'website_id',
        'page_url',
        'session_hash',
        'visitor_ip',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    // Consider visitor active if seen in last 5 minutes
    public static function getActiveCount(
        int $websiteId, 
        string $pageUrl = null,
        int $minutes = 5
    ): int {
        $query = static::where('website_id', $websiteId)
            ->where('last_seen_at', '>=', 
                now()->subMinutes($minutes));

        if ($pageUrl) {
            $query->where('page_url', $pageUrl);
        }

        return $query->count();
    }
}
