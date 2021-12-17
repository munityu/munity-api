<?php

namespace App\Models;

class User extends \Illuminate\Foundation\Auth\User implements \Tymon\JWTAuth\Contracts\JWTSubject
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

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

    protected $spatialFields = [
        'location',
    ];

    protected $casts = [
        'name' => 'string',
        'email' => 'string',
        'password' => 'string',
        'image' => 'string',
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
