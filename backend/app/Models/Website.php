<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Website extends Model
{
    protected $fillable = [
        'user_id', 'name', 'domain', 'pixel_id', 'is_active',
        'display_for', 'display_last', 'display_from_days', 'display_from_hours', 'display_from_minutes',
        'loop', 'link_open', 'show_on_display', 'close_button', 'hide_on_mobile',
        'theme', 'image_shape', 'widget_position', 'background_color', 'text_color', 'accent_color', 'custom_css', 'custom_css_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'loop' => 'boolean',
        'link_open' => 'boolean',
        'close_button' => 'boolean',
        'hide_on_mobile' => 'boolean',
        'custom_css_active' => 'boolean',
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
