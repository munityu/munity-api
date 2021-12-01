<?php

namespace App\Models;

class Event extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory, \Illuminate\Notifications\Notifiable;

    protected $fillable = [
        'name',
        'description',
        'price',
        'date',
        'address',
        'format',
        'theme',
    ];

    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'price' => 'float',
        'date' => 'datetime',
        'address' => 'string',
        'format' => 'string',
        'theme' => 'string',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class)->withPivot(['owner'])->as('calendar_user');
    }

    public function owner()
    {
        return $this->belongsToMany(Event::class)->withPivot(['owner'])->wherePivot('owner', true)->as('event_user');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
