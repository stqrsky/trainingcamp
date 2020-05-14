<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('image_id')->nullable();
            $table->tinyInteger('status')->nullable()->default(0);
            $table->timestamps();

            $table->foreign('image_id')->references('id')->on('images');
        });

        Schema::create('team_coach', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('team_athlete', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_athlete');
        Schema::dropIfExists('team_coach');
        Schema::dropIfExists('teams');
    }
}
