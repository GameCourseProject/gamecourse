<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAwardParticipationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('award_participations', function (Blueprint $table) {
            $table->foreignId('award_id');
            $table->foreignId('participation_id');
            $table->timestamp('created_at')
                ->useCurrent();
            $table->timestamp('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->primary(['award_id', 'participation_id']);
            $table->foreign('award_id')
                ->references('id')
                ->on('awards')
                ->onDelete('cascade');
            $table->foreign('participation_id')
                ->references('id')
                ->on('participations')
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
        Schema::dropIfExists('award_participations');
    }
}
