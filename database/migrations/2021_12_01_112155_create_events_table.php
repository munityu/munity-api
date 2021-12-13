<?php

use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        Schema::create('events', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('title', 64);
            $table->string('description', 2048);
            $table->string('poster', 128)->nullable();
            $table->enum('format', ['Conference', 'Seminar', 'Workshop', 'Class', 'Party', 'Fest', 'Con', 'Show/Expo']);
            $table->enum('theme', ['Business', 'Politics', 'Psychology', 'Education', 'Entertainment', "Music", "Art"]);
            $table->unsignedFloat('price')->nullable();
            $table->point('location');
            $table->dateTime('date');
            $table->boolean('nv_notifications')->default(false);
            $table->boolean('public_visitors')->default(false);
            $table->string('promocode', 16)->nullable();
            $table->string('page', 128)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
}
