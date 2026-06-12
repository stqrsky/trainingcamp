<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 'schedules';

    protected $guarded = ['id'];

    public function participants()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'schedule_participant'
        );
    }

    public function getStartAttribute($start)
    {
        return Carbon::parse($start)->format('H:i');
    }

    public function getEndAttribute($end)
    {
        return Carbon::parse($end)->format('H:i');
    }

    public function getDateFormatAttribute()
    {
        return Carbon::parse($this->date)->format('d/m/Y');
    }

    public function getStartEndTimeAttribute()
    {
        $start = Carbon::parse($this->start)->format('h:i a');
        $end = Carbon::parse($this->end)->format('h:i a');
        return "$start - $end";
    }

    public function getDurationMinutesAttribute(): int
    {
        [$sh, $sm] = explode(':', $this->start);
        [$eh, $em] = explode(':', $this->end);
        return ($eh * 60 + $em) - ($sh * 60 + $sm);
    }

    public function getVideoIconAttribute(): string
    {
        return match($this->video_type) {
            'zoom'        => '📹',
            'google_meet' => '📹',
            'gotomeeting' => '📹',
            default       => '📹',
        };
    }

    public function getVideoLabelAttribute(): string
    {
        return match($this->video_type) {
            'zoom'        => 'Zoom',
            'google_meet' => 'Google Meet',
            'gotomeeting' => 'GoToMeeting',
            default       => 'Video Call',
        };
    }

    public function getColorHexAttribute(): string
    {
        return match($this->color) {
            'green'  => '#16a34a',
            'red'    => '#dc2626',
            'orange' => '#ea580c',
            'purple' => '#7c3aed',
            'pink'   => '#db2777',
            'teal'   => '#0d9488',
            'amber'  => '#d97706',
            default  => '#2563eb', // blue
        };
    }

    public function getColorBgAttribute(): string
    {
        return match($this->color) {
            'green'  => '#dcfce7',
            'red'    => '#fee2e2',
            'orange' => '#ffedd5',
            'purple' => '#ede9fe',
            'pink'   => '#fce7f3',
            'teal'   => '#ccfbf1',
            'amber'  => '#fef3c7',
            default  => '#dbeafe', // blue
        };
    }
}
