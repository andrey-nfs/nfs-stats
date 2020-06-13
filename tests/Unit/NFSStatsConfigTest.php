<?php

namespace Tests\Unit;

use Tests\TestCase;

use Illuminate\Foundation\Testing\DatabaseMigrations;

use App\NFSStatsConfig;
use ConfigSeeder;

class NFSStatsConfigTest extends TestCase
{
    use DatabaseMigrations;    

    public function testIncludeUnfinishedGamesSettingIsTrueByDefault()
    {
        if (NFSStatsConfig::all()->count() === 0) {
            $this->seed(ConfigSeeder::class);
        }

        $this->assertEquals('1', NFSStatsConfig::getValue('include_unfinished_games'));
    }
}
