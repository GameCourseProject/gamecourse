<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDictionaryFunctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
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
            $table->timestamp('created_at')
                ->useCurrent();
            $table->timestamp('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->foreign('library')
                ->references('id')
                ->on('dictionary_library')
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
        Schema::dropIfExists('dictionary_functions');
    }
}
