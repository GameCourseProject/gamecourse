<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAwardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable(false);
            $table->foreignId('course_id')
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
            $table->timestamp('created_at')
                ->useCurrent();
            $table->timestamp('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->foreign(['user_id', 'course_id'])
                ->references(['user_id', 'course_id'])
                ->on('course_users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('awards');
    }
}
