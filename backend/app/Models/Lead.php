<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['website_id', 'email'];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }
}
