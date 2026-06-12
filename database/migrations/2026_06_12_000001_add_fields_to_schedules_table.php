<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToSchedulesTable extends Migration
{
    public function up()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('title')->nullable()->after('user_id');
            $table->string('location')->nullable()->after('title');
            $table->text('notes')->nullable()->after('location');
            $table->string('video_url', 512)->nullable()->after('notes');
            $table->string('video_type', 20)->nullable()->after('video_url');
            $table->string('color', 20)->nullable()->default('blue')->after('video_type');
        });
    }

    public function down()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['title', 'location', 'notes', 'video_url', 'video_type', 'color']);
        });
    }
}
