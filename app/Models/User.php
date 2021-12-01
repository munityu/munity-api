<?php

namespace App\Models;

class User extends \Illuminate\Foundation\Auth\User implements \Tymon\JWTAuth\Contracts\JWTSubject
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory, \Illuminate\Notifications\Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'image',
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'name' => 'string',
        'email' => 'string',
        'password' => 'string',
        'role' => 'string',
        'image' => 'string',
    ];

    public function events()
    {
        return $this->belongsToMany(Event::class)->withPivot(['owner'])->as('event_user');
    }

    public function owner()
    {
        return $this->belongsToMany(Event::class)->withPivot(['owner'])->wherePivot('owner', true)->as('event_user');
    }

    public function members()
    {
        return $this->belongsToMany(Event::class)->withPivot(['owner'])->wherePivot('owner', false)->as('event_user');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
