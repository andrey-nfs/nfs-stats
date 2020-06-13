<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompositePlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('composite_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('primary_player_id');
            $table->unsignedBigInteger('secondary_player_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('primary_player_id')->references('id')->on('players');
            $table->foreign('secondary_player_id')->references('id')->on('players');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('composite_players');
    }
}
