<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sparrings get a workflow status (planned → completed or cancelled), a goal and a result.
 */
class AddWorkflowFieldsToSchedulesTable extends Migration
{
    public function up()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('status', 20)->default('planned')->change();
            $table->text('goal')->nullable()->after('notes');
            $table->text('result')->nullable()->after('goal');
            $table->index(['team_id', 'date']);
        });

        // Sessions in the past took place as far as we know; everything else is still planned.
        $today = now()->toDateString();
        DB::table('schedules')->where('date', '<', $today)->update(['status' => 'completed']);
        DB::table('schedules')->where('date', '>=', $today)->update(['status' => 'planned']);
    }

    public function down()
    {
        DB::table('schedules')->where('status', 'cancelled')->update(['status' => '0']);
        DB::table('schedules')->where('status', '!=', '0')->update(['status' => '1']);

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'date']);
            $table->dropColumn(['goal', 'result']);
        });
        Schema::table('schedules', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable()->default(0)->change();
        });
    }
}
