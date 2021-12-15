<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

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
