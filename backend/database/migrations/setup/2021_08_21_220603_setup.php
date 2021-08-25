<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class Setup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('SET SQL_MODE="ALLOW_INVALID_DATES"');

        Artisan::call('migrate', ['--path' => 'database/migrations/users']);
        Artisan::call('migrate', ['--path' => 'database/migrations/auth']);

        Artisan::call('migrate', ['--path' => 'database/migrations/courses']);
        Artisan::call('migrate', ['--path' => 'database/migrations/courses/users']);

        Artisan::call('migrate', ['--path' => 'database/migrations/roles']);
        Artisan::call('migrate', ['--path' => 'database/migrations/users/roles']);

        Artisan::call('migrate', ['--path' => 'database/migrations/modules']);
        Artisan::call('migrate', ['--path' => 'database/migrations/courses/modules']);

        Artisan::call('migrate', ['--path' => 'database/migrations/dictionary']);

        Artisan::call('migrate', ['--path' => 'database/migrations/awards']);

        Artisan::call('migrate', ['--path' => 'database/migrations/notifications']);

        Artisan::call('migrate', ['--path' => 'database/migrations/participations']);
        Artisan::call('migrate', ['--path' => 'database/migrations/awards/participations']);

        Artisan::call('migrate', ['--path' => 'database/migrations/views']);
        Artisan::call('migrate', ['--path' => 'database/migrations/views/parent']);
        Artisan::call('migrate', ['--path' => 'database/migrations/pages']);
        Artisan::call('migrate', ['--path' => 'database/migrations/templates']);
        Artisan::call('migrate', ['--path' => 'database/migrations/views/templates']);

        Artisan::call('migrate', ['--path' => 'database/migrations/autogame']);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('autogame')->insert([
            'course' => 0,
            'is_running' => false
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // TODO: add trigger when delete level or badge -> delete badge_has_level
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/users']);
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/auth']);

        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/courses']);
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/courses/users']);

        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/roles']);
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/users/roles']);

        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/modules']);
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/courses/modules']);

        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/dictionary']);

        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/awards']);

        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/notifications']);

        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/participations']);
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/awards/participations']);

        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/views']);
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/views/parent']);
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/pages']);
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/templates']);
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/views/templates']);

        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/autogame']);
    }
}
