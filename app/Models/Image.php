<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $table = 'images';

    protected $guarded = ['id'];

    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function teams()
    {
        return $this->hasMany(\App\Models\Team::class);
    }
}
