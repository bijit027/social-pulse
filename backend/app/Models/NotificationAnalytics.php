<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationAnalytics extends Model
{
    protected $fillable = [
        'notification_id',
        'views',
        'clicks',
        'created_at'
    ];

    protected $casts = [
        'views' => 'integer',
        'clicks' => 'integer',
        'created_at' => 'date'
    ];

    public $timestamps = false;

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Insert or increment analytics for a notification
     */
    public static function track($notificationId, $type = 'views')
    {
        $today = now()->toDateString();
        
        try {
            $analytics = self::where('notification_id', $notificationId)
                ->where('created_at', $today)
                ->first();

            if ($analytics) {
                $analytics->increment($type);
            } else {
                self::create([
                    'notification_id' => $notificationId,
                    'views' => $type === 'views' ? 1 : 0,
                    'clicks' => $type === 'clicks' ? 1 : 0,
                    'created_at' => $today
                ]);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle race condition - if record was created by another request, try to increment
            if ($e->getCode() == 23000) { // UNIQUE constraint violation
                $analytics = self::where('notification_id', $notificationId)
                    ->where('created_at', $today)
                    ->first();
                if ($analytics) {
                    $analytics->increment($type);
                }
            } else {
                throw $e;
            }
        }
    }

    /**
     * Check if analytics should be counted (bot detection)
     */
    public static function shouldCount()
    {
        $userAgent = request()->userAgent() ?? '';
        
        // Bot detection - same as NotificationX
        $bots = [
            'googlebot',
            'msnbot',
            'ia_archiver',
            'lycos',
            'jeeves',
            'scooter',
            'fast-webcrawler',
            'slurp@inktomi',
            'turnitinbot',
            'technorati',
            'yahoo',
            'findexa',
            'findlinks',
            'gaisbo',
            'zyborg',
            'surveybot',
            'bloglines',
            'blogsearch',
            'pubsub',
            'syndic8',
            'userland',
            'gigabot',
            'become.com',
            'baiduspider',
            '360spider',
            'spider',
            'sosospider',
            'yandex'
        ];

        foreach ($bots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return false;
            }
        }

        return true;
    }
}
