<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weekly time windows in which a member is available (and prefers) to spar.
 */
class CreateAvailabilitiesTable extends Migration
{
    public function up()
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('weekday'); // ISO: 1 = Monday … 7 = Sunday
            $table->time('start');
            $table->time('end');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'weekday']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('availabilities');
    }
}
