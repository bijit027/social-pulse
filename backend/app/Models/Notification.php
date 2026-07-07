<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'website_id', 'type', 'message',
        'city', 'country', 'emoji', 'product_url',
        'is_active', 'display_order', 'source',
        'rating', 'button_text'
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function displays()
    {
        return $this->hasMany(NotificationDisplay::class, 'notification_id');
    }
}
