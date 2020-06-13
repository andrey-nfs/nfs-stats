<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('steamId');
            $table->string('nickname');
            $table->smallInteger('games')->unsigned()->nullable();
            $table->mediumInteger('rounds')->unsigned()->nullable();
            $table->mediumInteger('spawned_with_bomb')->unsigned()->nullable();
            $table->mediumInteger('planted_bomb')->unsigned()->nullable();
            $table->mediumInteger('hostages_killed')->unsigned()->nullable();
            $table->mediumInteger('hostages_saved')->unsigned()->nullable();
            $table->mediumInteger('kills')->unsigned()->nullable();
            $table->mediumInteger('headshot_kills')->unsigned()->nullable();
            $table->mediumInteger('team_kills')->unsigned()->nullable();
            $table->mediumInteger('deaths')->unsigned()->nullable();
            $table->mediumInteger('headshot_deaths')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('players');
    }
}
