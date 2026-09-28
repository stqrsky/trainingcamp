<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Members can be set inactive in a team instead of being removed: they keep their history
 * but no longer show up as sparring participants or task assignees.
 */
class AddActiveToTeamMembershipTables extends Migration
{
    public function up()
    {
        foreach (['team_athlete', 'team_coach'] as $pivot) {
            Schema::table($pivot, function (Blueprint $table) {
                $table->boolean('active')->default(true);
            });
        }
    }

    public function down()
    {
        foreach (['team_athlete', 'team_coach'] as $pivot) {
            Schema::table($pivot, function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }
    }
}
