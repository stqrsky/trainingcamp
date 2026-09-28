<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Members (athletes and coaches added to a team) are managed profiles without a login.
 * Only account holders can sign in, and an account can own several teams.
 */
class AddMultiTeamFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->boolean('login_enabled')->default(false)->after('password');
            $table->unsignedBigInteger('current_team_id')->nullable()->after('login_enabled');
        });

        // Everyone keeps their login except members of a team owned by someone else.
        $ownerIds = DB::table('teams')->whereNotNull('user_id')->pluck('user_id');
        $memberIds = collect(['team_athlete', 'team_coach'])->flatMap(function ($pivot) {
            return DB::table($pivot)
                ->join('teams', 'teams.id', '=', "$pivot.team_id")
                ->whereColumn("$pivot.user_id", '!=', 'teams.user_id')
                ->pluck("$pivot.user_id");
        })->diff($ownerIds)->unique();

        DB::table('users')->whereNotIn('id', $memberIds)->update(['login_enabled' => true]);

        DB::table('teams')->whereNotNull('user_id')->orderBy('id')->get(['id', 'user_id'])
            ->unique('user_id')
            ->each(function ($team) {
                DB::table('users')->where('id', $team->user_id)->update(['current_team_id' => $team->id]);
            });
    }

    public function down()
    {
        // email and password stay nullable: member rows may no longer have credentials.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_enabled', 'current_team_id']);
        });
    }
}
