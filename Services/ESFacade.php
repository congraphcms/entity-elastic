<?php

namespace Congraph\EntityElastic\Services;

use Illuminate\Support\Facades\Facade as BaseFacade;


/**
 * Class Facade
 *
 * @package Cviebrock\LaravelElasticsearch
 */
class ESFacade extends BaseFacade
{

    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return 'cb.elastic';
    }
}