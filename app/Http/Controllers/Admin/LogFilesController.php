<?php

namespace App\Http\Controllers\Admin;

use App\CompositePlayer;
use DateTime;

use App\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

use App\Http\Controllers\Controller;

use App\NFSStatsConfig;
use App\LogFile;
use App\Map;
use App\Player;

class LogFilesController extends Controller
{    
    /**
     * Process all logs.
     *
     * @return null;
     */
    public function processAll()
    {
        $logDirectories = Arr::where(Storage::allDirectories(), function($value, $key) {
            return Str::contains($value, NFSStatsConfig::getValue('logs_folder_prefix'));
        });

        echo '- Looking for log files<br>';

        foreach ($logDirectories as $dir) {
            echo '-- Looking in ' . $dir . '<br>';

            $files = Storage::files($dir);
            
            foreach ($files as $file) {
                $pathinfo = pathinfo($file);

                if ($pathinfo['extension'] === 'log') {
                    echo '--- Found a log file: ' . $pathinfo['basename'] . '<br>';

                    $currentLogFile = LogFile::firstOrCreate(
                        ['file' => $file],
                        [
                            'game' => Str::after($dir, NFSStatsConfig::getValue('logs_folder_prefix')),
                            'name' => $pathinfo['basename'],
                        ]
                    );

                    if ($currentLogFile->wasRecentlyCreated) {
                        echo '---- Outcome: File recorded.<br>';

                        $handle = fopen(getcwd() . '/../storage/app/' . $file, 'r');

                        echo '---- Reading file contents<br>';

                        // get a log file
                        if ($handle) {
                            $isGamelog = true;
                            $isUnfinishedGame = true;
                            $gameStartDateTime = null;
                            $gameEndDateTime = null;

                            // loop through each line
                            while (($buffer = fgets($handle)) !== false) {
                                $line = Str::of($buffer);

                                // skip first line (to simplify further code)
                                if ($line->contains('Log file started')) {
                                    continue;
                                }

                                // check if the log is pre-game
                                if ($line->contains('Loading map')) {
                                    $isGamelog = false;

                                    echo '---- Detected a pre-game log<br>';

                                    $mapName = $line->afterLast('map ')->trim('"');
                                };

                                // checks that are exclusive for game logs 
                                if ($isGamelog) {

                                    // get date and time of when the game started
                                    $gameStartDateTime = $this->getTimestamp($line);

                                    // get players
                                    if ($line->contains('entered the game') || $line->contains('joined team')) {
                                        // get Steam ID or determine if the player is a bot
                                        $steamId = $line->beforeLast('><>"')->afterLast('><');

                                        // skip the line if the player is a bot and they shouldn't be included
                                        if ($steamId === 'BOT' && !NFSStatsConfig::getValue('include_bots')) {
                                            continue;
                                        }

                                        // get a nickname
                                        $nicknameWithIdString = $line->beforeLast($steamId)->after(': "');
                                        $nicknameWithIdArray = explode('<', $nicknameWithIdString);
                                        $id = $nicknameWithIdArray[array_key_last($nicknameWithIdArray)];
                                        $nickname = Str::of($nicknameWithIdString)->beforeLast($id);

                                        // save the player is it doesn't exist
                                        if (NFSStatsConfig::getValue('default_player_id') === 'steamId') {
                                            $currentPlayer = Player::firstOrCreate(
                                                ['steamId' => $steamId],
                                                [
                                                    'nickname' => $nickname,
                                                ]
                                            );
                                        } else {
                                            $currentPlayer = Player::firstOrCreate(
                                                ['nickname' => $nickname],
                                                [
                                                    'steamId' => $steamId,
                                                ]
                                            );
                                        }                                        

                                        // create a new composite player record for the current player if it doesn't exist
                                        $primaryCompositePlayer = CompositePlayer::where('primary_player_id', $currentPlayer->id) ?? false;
                                        $secondaryCompositePlayer = CompositePlayer::where('secondary_player_id', $currentPlayer->id) ?? false;

                                        if (!$primaryCompositePlayer && !$secondaryCompositePlayer) {
                                            CompositePlayer::create([
                                                'primary_player_id' => $currentPlayer->id,
                                            ]);
                                        } else if ($primaryCompositePlayer && $secondaryCompositePlayer) {
                                            die('FATAL ERORR. Found that player #' . $currentPlayer->id . ' has both primary and secondary composite player associations.');
                                        }                                        

                                        // get clan tag symbols specified in the stats configuration
                                        $clanTagSymbols = explode(',', NFSStatsConfig::getValue('clan_tag_symbols'));
                                        $clans = [];

                                        // multiple clan tags are allowed so we search for them accordingly
                                        foreach ($clanTagSymbols as $clanTagSymbol) {
                                            $clanTagOpen = str_split($clanTagSymbol)[0];
                                            $clanTagClose = str_split($clanTagSymbol)[1];

                                            // check if the nickname contains both opening and closing symbols of the same type
                                            if (Str::containsAll($nickname, [$clanTagOpen, $clanTagClose])) {
                                                // get a string (dirty clan tag) between the symbols
                                                $clanName = Str::of($nickname)->afterLast($clanTagOpen)->before($clanTagClose);

                                                // clean the possible clan tag from non-alphanumeric caracters
                                                $clanName = preg_replace('/[^a-zA-Z0-9]/','', $clanName);

                                                foreach ($clans as $clan) {
                                                    if (!Str::contains($clan, $clanName)) {
                                                        $clans[] = $clanName;
                                                    }
                                                }
                                            };
                                        }
                                    }

                                    // check if the log is closed
                                    if ($line->contains('Log file closed')) {
                                        $isUnfinishedGame = false;

                                        // get date and time of when the game ended
                                        $gameEndDateTime = $this->getTimestamp($line);

                                        echo "---- Log is closed; Game end date and time recorded ($gameEndDateTime)<br>";
                                    }
                                }
                            }

                            // proceed if game is finished or the system is instructed to record finished games
                            if (($isUnfinishedGame && NFSStatsConfig::getValue('include_unfinished_games')) || !$isUnfinishedGame) {

                                // check if the logfile is game or pregame
                                if ($isGamelog) {
                                    echo '---- Find a pre-game log for this game<br>';

                                    // try to find the pregame log for this game log
                                    $lastGame = Game::latest()->first();

                                    // if no pregame log found
                                    if (!$lastGame || $lastGame->game_logfile_id !== null) {
                                        $defaultMapName = NFSStatsConfig::getValue('default_map_guess');

                                        // if default map name specified
                                        if ($defaultMapName) {

                                            // get default map instance or create if it doesn't exist
                                            $defaultMap = $this->firstOrCreateMap($defaultMapName);

                                            // create a new game record using default map
                                            $newGame = Game::create([
                                                'map_id' => $defaultMap->id,
                                                'game_logfile_id' => $currentLogFile->id,
                                                'game_started_at' => $gameStartDateTime,
                                                'game_ended_at' => $gameEndDateTime,
                                            ]);
                                        }
                                    }
                                } else {
                                    echo '---- Find existing or create a new record for the map<br>';

                                    // get map instance or create if it doesn't exist
                                    $map = $this->firstOrCreateMap($mapName);

                                    // create a new game record
                                    Game::create([
                                        'map_id' => $map->id,
                                        'pregame_logfile_id' => $currentLogFile->id,
                                    ]);
                                }
                            } else {
                                echo '---- Game is unfinished, log not recorded<br>';
                            }

                            if (!feof($handle)) {
                                echo '---- Error: unexpected fgets() fail<br>';
                            }

                            fclose($handle);
                        }
                    } else {                        
                        echo '---- Outcome: File is already in the database.<br>';
                    }
                }
            }
        }
    }

    /**
     * Remove all log file records from the database.
     *
     * @return null;
     */
    public function removeAll()
    {
        echo '- Trying to remove all the log file records<br>';

        LogFile::truncate();
        $result = LogFile::all()->count() === 0 ? 'successful' : 'failed';

        echo '-- Operation ' . $result . '<br>';
    }

    /**
     * Return the result of Map::firstOrCreate()
     *
     * @return Map;
     */
    public function firstOrCreateMap($name)
    {
        return Map::firstOrCreate(
            ['name' => $name],
            ['image' => '']
        );
    }

    /**
     * Convert log's datetime format to the one in our database
     *
     * @return DateTime;
     */
    public function getTimestamp($line)
    {
        return DateTime::createFromFormat('m/d/Y - H:i:s', $line->after('L ')->beforeLast(':'));
    }
}
