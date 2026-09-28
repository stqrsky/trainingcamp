<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tasks move from done/pending flags to a workflow status (backlog → done),
 * get four priority levels and an optional assignee from the team.
 */
class AddWorkflowFieldsToTasksTable extends Migration
{
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status', 20)->default('todo')->change();
            $table->string('priority', 10)->default('medium')->change();
            $table->unsignedBigInteger('assignee_id')->nullable()->after('user_id');

            $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['team_id', 'status']);
        });

        DB::table('tasks')->where('status', '1')->update(['status' => 'done']);
        DB::table('tasks')->where('status', '0')->update(['status' => 'todo']);
        DB::table('tasks')->where('priority', '1')->update(['priority' => 'high']);
        DB::table('tasks')->where('priority', '0')->update(['priority' => 'medium']);
    }

    public function down()
    {
        DB::table('tasks')->where('status', 'done')->update(['status' => '1']);
        DB::table('tasks')->where('status', '!=', '1')->update(['status' => '0']);
        DB::table('tasks')->whereIn('priority', ['high', 'urgent'])->update(['priority' => '1']);
        DB::table('tasks')->where('priority', '!=', '1')->update(['priority' => '0']);

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['assignee_id']);
            $table->dropIndex(['team_id', 'status']);
            $table->dropColumn('assignee_id');
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)->change();
            $table->tinyInteger('priority')->default(0)->change();
        });
    }
}
