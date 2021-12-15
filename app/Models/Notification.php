<?php

namespace App\Models;

class Notification extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'event_id',
        'content',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'content' => 'string',
    ];

    public function event()
    {
        return $this->hasOne(Event::class);
    }
}
