<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        Schema::create('game_course_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)
                ->nullable(false);
            $table->string('email', 255);
            $table->string('major', 8);
            $table->string('nickname', 50);
            $table->integer('student_number')
                ->unique();
            $table->boolean('is_admin')
                ->nullable(false)
                ->default(false);
            $table->boolean('is_active')
                ->nullable(false)
                ->default(true);
        });

        Schema::create('auth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_course_user_id')
                ->nullable(false);
            $table->string('username', 50);
            $table->enum('authentication_service', ['fenix','google','facebook','linkedin']);
            $table->foreign('game_course_user_id')
                ->references('id')
                ->on('game_course_users')
                ->onDelete('cascade');
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('short', 20);
            $table->string('color', 7);
            $table->string('year', 10);
            $table->string('default_landing_page', 100)
                ->default('');
            $table->timestamp('last_update')
                ->useCurrent();
            $table->boolean('is_active')
                ->default(true);
            $table->boolean('is_visible')
                ->default(true);
            $table->text('role_hierarchy');
            $table->string('theme', 50);
        });

        Schema::create('course_users', function (Blueprint $table) {
            $table->foreignId('id');
            $table->foreignId('course');
            $table->timestamp('last_activity');
            $table->timestamp('previous_activity');
            $table->boolean('is_active')
                ->nullable(false)
                ->default(true);
            $table->primary(['id', 'course']);
            $table->foreign('id')
                ->references('id')
                ->on('game_course_users')
                ->onDelete('cascade');
            $table->foreign('course')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)
                ->nullable(false);
            $table->string('landing_page', 100)
                ->default('');
            $table->foreignId('course')
                ->nullable(false);
            $table->boolean('is_course_admin')
                ->default(false);
            $table->foreign('course')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('id')
                ->nullable(false);
            $table->foreignId('course')
                ->nullable(false);
            $table->foreignId('role')
                ->nullable(false);
            $table->primary(['id', 'course', 'role']);
            $table->foreign(['id', 'course'])
                ->references(['id', 'course'])
                ->on('course_users')
                ->onDelete('cascade');
            $table->foreign('role')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('description', 100);
            $table->string('version', '10');
            $table->string('compatible_versions', 100);
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->foreignId('module')
                ->nullable(false);
            $table->foreignId('course')
                ->nullable(false);
            $table->boolean('is_enabled')
                ->default(false);
            $table->primary(['module', 'course']);
            $table->foreign('module')
                ->references('id')
                ->on('modules')
                ->onDelete('cascade');
            $table->foreign('course')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');
        });

        Schema::create('dictionary_library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module')
                ->nullable(false);
            $table->string('name', 50)
                ->nullable(false);
            $table->string('description', 255);
            $table->foreign('module')
                ->references('id')
                ->on('modules')
                ->onDelete('cascade');
        });

        Schema::create('dictionary_functions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library');
            $table->string('return_type', 50);
            $table->string('return_name', 50);
            $table->string('refers_to_type', 50)
                ->nullable(false);
            $table->string('refers_to_name', 50);
            $table->string('keyword', 50);
            $table->string('args', 100);
            $table->string('description', 1000);
            $table->foreign('library')
                ->references('id')
                ->on('dictionary_library')
                ->onDelete('cascade');
        });

        Schema::create('dictionary_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library');
            $table->string('name', 50)
                ->unique();
            $table->string('return_type', 50)
                ->nullable(false);
            $table->string('return_name', 50);
            $table->string('description', 1000);
            $table->foreign('library')
                ->references('id')
                ->on('dictionary_library')
                ->onDelete('cascade');
        });

        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user')
                ->nullable(false);
            $table->foreignId('course')
                ->nullable(false);
            $table->string('description', 100)
                ->nullable(false);
            $table->string('type', 50)
                ->nullable(false); // ex:grade,skills, labs,quiz,presentation,bonus FIXME: should be enum
            $table->unsignedInteger('module_instance'); // id of badge/skill (will be null for other types)
            $table->unsignedInteger('reward')
                ->default(0);
            $table->timestamp('date')
                ->useCurrent();
            $table->foreign(['user', 'course'])
                ->references(['id', 'course'])
                ->on('course_users')
                ->onDelete('cascade');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award')
                ->nullable(false);
            $table->boolean('checked')
                ->default(false);
            $table->foreign('award')
                ->references('id')
                ->on('awards')
                ->onDelete('cascade');
        });

        Schema::create('participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user')
                ->nullable(false);
            $table->foreignId('course')
                ->nullable(false);
            $table->string('description', 500)
                ->nullable(false);
            $table->string('type', 50)
                ->nullable(false); // ex:grade,skill,badge, lab,quiz,presentation,bonus FIXME: should be enum
            $table->string('post', 255);
            $table->timestamp('date');
            $table->integer('rating');
            $table->foreignId('evaluator');
            $table->foreign(['evaluator', 'course'])
                ->references(['id', 'course'])
                ->on('course_users')
                ->onDelete('cascade');
            $table->foreign(['user', 'course'])
                ->references(['id', 'course'])
                ->on('course_users')
                ->onDelete('cascade');
        });

        Schema::create('award_participations', function (Blueprint $table) {
            $table->foreignId('award');
            $table->foreignId('participation');
            $table->primary(['award', 'participation']);
            $table->foreign('award')
                ->references('id')
                ->on('awards')
                ->onDelete('cascade');
            $table->foreign('participation')
                ->references('id')
                ->on('participations')
                ->onDelete('cascade');
        });

        Schema::create('views', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('view_id');
            $table->string('role', 100)
                ->default('role.Default');
            $table->enum('part_type', ['block','text','image','table','headerRow','row','header','chart']);
            $table->string('label', 50);
            $table->string('loop_data', 200);
            $table->string('variables', 500);
            $table->string('value', 200);
            $table->string('class', 50);
            $table->string('css_id', 50);
            $table->string('style', 200);
            $table->string('link', 100);
            $table->string('visibility_condition', 200);
            $table->enum('visibility_type', ['visible', 'invisible', 'conditional']);
            $table->string('events', 500);
            $table->string('info', 500);
        });

        Schema::create('views_parent', function (Blueprint $table) {
            $table->foreignId('parent_id');
            $table->unsignedInteger('child_id');
            $table->unsignedInteger('view_index');
            $table->foreign('parent_id')
                ->references('id')
                ->on('views')
                ->onDelete('cascade');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course')
            ->nullable(false);
            $table->string('name', 50)
                ->nullable(false);
            $table->string('theme', 50);
            $table->unsignedInteger('view_id');
            $table->boolean('is_enabled')
                ->default(false);
            $table->unsignedInteger('seq_id')
                ->nullable(false);
            $table->foreign('course')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)
                ->nullable(false);
            $table->enum('role_type', ['ROLE_SINGLE','ROLE_INTERACTION'])
                ->default('ROLE_SINGLE');
            $table->foreignId('course')
                ->nullable(false);
            $table->boolean('is_global')
                ->default(false);
            $table->foreign('course')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');
        });

        Schema::create('view_templates', function (Blueprint $table) {
            $table->unsignedInteger('view_id');
            $table->foreignId('template_id');
            $table->primary('view_id');
            $table->foreign('template_id')
                ->references('id')
                ->on('templates')
                ->onDelete('cascade');
        });

        Schema::create('autogame', function (Blueprint $table) {
            $table->foreignId('course')
                ->nullable(false);
            $table->timestamp('started_running')
                ->useCurrent();
            $table->timestamp('finished_running')
                ->useCurrent();
            $table->boolean('is_running')
                ->default(false);
            $table->primary('course');
            $table->foreign('course')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('autogame')->insert([
            'course' => 0,
            'is_running' => false
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // TODO: add trigger when delete level or badge -> delete bagde_has_level
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_course_users');
        Schema::dropIfExists('auth');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('course_users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('dictionary_library');
        Schema::dropIfExists('dictionary_functions');
        Schema::dropIfExists('dictionary_variables');
        Schema::dropIfExists('awards');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('participations');
        Schema::dropIfExists('award_participations');
        Schema::dropIfExists('views');
        Schema::dropIfExists('views_parent');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('view_templates');
        Schema::dropIfExists('autogame');
    }
}
