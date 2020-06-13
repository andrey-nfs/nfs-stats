<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained()->nullable();
            $table->unsignedBigInteger('pregame_logfile_id')->nullable();
            $table->unsignedBigInteger('game_logfile_id')->nullable();
            $table->dateTime('game_started_at')->nullable();
            $table->dateTime('game_ended_at')->nullable();
            $table->timestamps();

            $table->foreign('pregame_logfile_id')->references('id')->on('log_files');
            $table->foreign('game_logfile_id')->references('id')->on('log_files');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
}
