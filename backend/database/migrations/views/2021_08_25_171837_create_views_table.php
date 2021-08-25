<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
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
            $table->timestamp('created_at')
                ->useCurrent();
            $table->timestamp('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('views');
    }
}
