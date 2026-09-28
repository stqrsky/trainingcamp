<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects group a team's tasks. Progress and members are derived from the tasks
 * (done/total and distinct assignees), so they are not stored.
 */
class CreateProjectsTable extends Migration
{
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->date('deadline')->nullable();
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['team_id', 'status']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('team_id');
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
        Schema::dropIfExists('projects');
    }
}
