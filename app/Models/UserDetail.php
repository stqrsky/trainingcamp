<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserDetail extends Model
{
    protected $table = 'user_detail';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function image()
    {
        return $this->belongsTo(\App\Models\Image::class);
    }

    public function getFormatDateOfBirthAttribute()
    {
        return Carbon::parse($this->date_of_birth)->format('d/m/Y');
    }

    public function getAgeAttribute()
    {
        $dob = $this->date_of_birth;
        $age = '-';
        if ($dob) {
            $age = Carbon::now()->diffInYears($dob) . " years old";
        }
        return $age;
    }
}
