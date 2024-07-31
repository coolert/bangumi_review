<?php

namespace App\Admin\Repositories;

use App\Models\AnimeOffline as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class AnimeOffline extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
