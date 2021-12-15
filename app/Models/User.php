<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'location',
        'role',
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'name' => 'string',
        'email' => 'string',
        'password' => 'string',
        'image' => 'string',
        'location' => 'string',
        'role' => 'string',
    ];

    public function events()
    {
        return $this->belongsToMany(Event::class)->withPivot(['organizer'])->as('event_user');
    }

    public function organizer()
    {
        return $this->belongsToMany(Event::class)->withPivot(['organizer'])->wherePivot('organizer', true)->as('event_user');
    }

    public function members()
    {
        return $this->belongsToMany(Event::class)->withPivot(['organizer'])->wherePivot('organizer', false)->as('event_user');
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
