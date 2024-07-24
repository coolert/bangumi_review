<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class DataSite extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'data_site';
}
