<?php

use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        Schema::create('comments', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('event_id');
            $table->string('content', 4096);
            $table->foreignId('user_id')->constrained();
            $table->foreignId('event_id')->constrained();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('comments');
    }
}
