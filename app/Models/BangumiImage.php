<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class BangumiImage extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'bangumi_image';
    protected $fillable = [
        'subject_id',
        'summary',
        'image',
    ];
}
