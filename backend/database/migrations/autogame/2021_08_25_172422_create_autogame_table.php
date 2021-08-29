<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAutogameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autogame', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->nullable(false);
            $table->timestamp('started_running')
                ->useCurrent();
            $table->timestamp('finished_running')
                ->useCurrent();
            $table->boolean('is_running')
                ->default(false);
            $table->timestamp('created_at')
                ->useCurrent();
            $table->timestamp('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->primary('course_id');
            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
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
        Schema::dropIfExists('autogame');
    }
}
