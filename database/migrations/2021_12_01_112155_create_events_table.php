<?php

use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        Schema::create('events', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name', 32);
            $table->string('description', 1024);
            $table->unsignedFloat('price')->nullable();
            $table->dateTime('date');
            $table->string('address', 64);
            $table->enum('format', ['Conference', 'Seminar', 'Workshop', 'Class', 'Party', 'Fest', 'Con', 'Show/Expo']);
            $table->enum('theme', ['Business', 'Politics', 'Psychology', 'Education', 'Entertainment']);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
}
