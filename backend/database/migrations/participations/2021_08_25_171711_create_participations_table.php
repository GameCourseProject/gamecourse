<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateParticipationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable(false);
            $table->foreignId('course_id')
                ->nullable(false);
            $table->string('description', 500)
                ->nullable(false);
            $table->string('type', 50)
                ->nullable(false); // ex:grade,skill,badge, lab,quiz,presentation,bonus FIXME: should be enum
            $table->string('post', 255);
            $table->timestamp('date');
            $table->integer('rating');
            $table->foreignId('evaluator_id');
            $table->timestamp('created_at')
                ->useCurrent();
            $table->timestamp('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->foreign(['evaluator_id', 'course_id'])
                ->references(['user_id', 'course_id'])
                ->on('course_users')
                ->onDelete('cascade');
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
        Schema::dropIfExists('participations');
    }
}
