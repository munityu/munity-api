<?php

namespace App\Models;

class Event extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

    protected $fillable = [
        'title',
        'description',
        'poster',
        'format',
        'theme',
        'date',
        'pub_date',
        'price',
        'location',
        'address',
        'nv_notifications',
        'public_visitors',
        'promocode',
        'page',
    ];

    protected $spatialFields = [
        'location',
    ];

    protected $casts = [
        'title' => 'string',
        'description' => 'string',
        'poster' => 'string',
        'format' => 'string',
        'theme' => 'string',
        'date' => 'datetime',
        'pub_date' => 'datetime',
        'price' => 'float',
        'address' => 'string',
        'nv_notifications' => 'bool',
        'public_visitors' => 'bool',
        'promocode' => 'string',
        'page' => 'string',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class)->withPivot(['organizer'])->as('event_user');
    }

    public function isMember(int $id): bool
    {
        foreach ($this->members as $member)
            if ($member->id == $id)
                return true;
        return false;
    }

    public function organizer()
    {
        return $this->belongsToMany(User::class)->withPivot(['organizer'])->wherePivot('organizer', true)->as('event_user');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
