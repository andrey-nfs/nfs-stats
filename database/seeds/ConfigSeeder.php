<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('config')->insert([
            [
                'key' => 'include_unfinished_games',
                'value' => true,
            ],
            [
                'key' => 'include_unknown_maps',
                'value' => true,
            ],
            [
                'key' => 'logs_folder_prefix',
                'value' => 'logs_',
            ],
            [
                'key' => 'default_map_guess',
                'value' => 'cs_pf_dust',
            ],
            [
                'key' => 'clan_tag_symbols',
                'value' => '[],{},<>,(),**,^^,@@,++,==,--,``,\'\',//,\\\\,$$,##,""',
            ],
            [
                'key' => 'default_player_id',
                'value' => 'steamId',
            ],
            [
                'key' => 'include_bots',
                'value' => true,
            ]
        ]);
    }
}
