<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Notification extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    public function image()
    {
        return $this->belongsTo(\App\Models\Image::class);
    }

    public function getTimeAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }
}
