<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Website extends Model
{
    protected $fillable = [
        'user_id', 'name', 'domain', 'pixel_id', 'is_active'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($website) {
            $website->pixel_id = Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationDisplays()
    {
        return $this->hasMany(NotificationDisplay::class, 'website_id');
    }
}
