<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The seeded "skills" Basic/Intermediate/Advance/Expert were experience levels. They move to
 * user_detail.experience_level; skills become a per-team catalog with a level per member.
 */
class AddSkillLevelsAndExperienceLevel extends Migration
{
    private const LEVEL_SKILLS = [
        'Basic'        => 'beginner',
        'Intermediate' => 'intermediate',
        'Advance'      => 'advanced',
        'Expert'       => 'expert',
    ];

    public function up()
    {
        Schema::table('user_detail', function (Blueprint $table) {
            $table->string('experience_level', 20)->nullable()->after('about');
        });
        Schema::table('skills', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable()->after('id');
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });
        Schema::table('user_skill', function (Blueprint $table) {
            $table->string('level', 20)->default('beginner');
        });

        $levelSkills = DB::table('skills')->whereNull('team_id')
            ->whereIn('name', array_keys(self::LEVEL_SKILLS))->pluck('name', 'id');
        if ($levelSkills->isEmpty()) {
            return;
        }

        // Keep each member's highest selected level
        $rank = array_flip(array_values(self::LEVEL_SKILLS));
        DB::table('user_skill')->whereIn('skill_id', $levelSkills->keys())->get()
            ->groupBy('user_id')
            ->each(function ($rows, $userId) use ($levelSkills, $rank) {
                $level = $rows->map(fn ($row) => self::LEVEL_SKILLS[$levelSkills[$row->skill_id]])
                    ->sortByDesc(fn ($level) => $rank[$level])->first();
                DB::table('user_detail')->updateOrInsert(
                    ['user_id' => $userId],
                    ['experience_level' => $level, 'updated_at' => now()]
                );
            });

        DB::table('user_skill')->whereIn('skill_id', $levelSkills->keys())->delete();
        DB::table('skills')->whereIn('id', $levelSkills->keys())->delete();
    }

    public function down()
    {
        // Recreate the level "skills" from experience_level so the old forms keep working.
        $levelToSkill = array_flip(self::LEVEL_SKILLS);
        foreach ($levelToSkill as $name) {
            DB::table('skills')->insertOrIgnore(['name' => $name, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        $skillIds = DB::table('skills')->whereNull('team_id')->whereIn('name', $levelToSkill)->pluck('id', 'name');
        DB::table('user_detail')->whereNotNull('experience_level')->get(['user_id', 'experience_level'])
            ->each(function ($detail) use ($levelToSkill, $skillIds) {
                DB::table('user_skill')->insert([
                    'user_id' => $detail->user_id,
                    'skill_id' => $skillIds[$levelToSkill[$detail->experience_level]],
                ]);
            });

        Schema::table('user_skill', function (Blueprint $table) {
            $table->dropColumn('level');
        });
        Schema::table('skills', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
        Schema::table('user_detail', function (Blueprint $table) {
            $table->dropColumn('experience_level');
        });
    }
}
