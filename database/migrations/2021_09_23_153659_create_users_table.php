<?php

use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name', 10)->unique();
            $table->string('email', 64)->unique();
            $table->string('password');
            $table->string('image')->default('https://d3djy7pad2souj.cloudfront.net/weevely/avatar1_weevely_H265P.png');
            $table->enum('role', ['user', 'company', 'admin'])->default('user');
            $table->point('location')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
