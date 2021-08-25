<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->timestamp('created_at')
                ->useCurrent();
            $table->timestamp('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->foreign(['evaluator', 'course'])
                ->references(['id', 'course'])
                ->on('course_users')
                ->onDelete('cascade');
            $table->foreign(['user', 'course'])
                ->references(['id', 'course'])
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
