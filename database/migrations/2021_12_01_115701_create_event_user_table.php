<?php

use Illuminate\Support\Facades\Schema;

class CreateEventUserTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        Schema::create('event_user', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->foreignId('event_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->boolean('organizer')->default(false);
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_user');
    }
}
