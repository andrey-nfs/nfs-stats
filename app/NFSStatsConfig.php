<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NFSStatsConfig extends Model
{    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'config';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'key';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];


    /**
     * A shorthand to retreive the settings' values.
     *
     * @param String $key Setting key
     * @return any Setting value
     **/
    public static function getValue($key)
    {
        return self::find($key)->value;
    }
}
