<?php

namespace App\Admin\Repositories;

use App\Models\DataItem as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Bangumi extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
