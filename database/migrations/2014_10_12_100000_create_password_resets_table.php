<?php

use Illuminate\Support\Facades\Schema;

class CreatePasswordResetsTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        Schema::create('password_resets', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('password_resets');
    }
}
