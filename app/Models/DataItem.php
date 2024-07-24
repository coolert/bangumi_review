<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class DataItem extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'data_item';
}
