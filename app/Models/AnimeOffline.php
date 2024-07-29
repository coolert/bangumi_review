<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class AnimeOffline extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'anime_offline';
}
