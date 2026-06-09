<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDisplay extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'website_id',
        'notification_id', 
        'visitor_ip',
        'displayed_at',
    ];
}