<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Handlers;

use Illuminate\Support\ServiceProvider;

use Congraph\EntityElastic\Handlers\Commands\Entities\EntityFetchHandler;
use Congraph\EntityElastic\Handlers\Commands\Entities\EntityGetHandler;

/**
 * HandlersServiceProvider service provider for handlers
 *
 * It will register all handlers to app container
 *
 * @uses   		Illuminate\Support\ServiceProvider
 *
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class HandlersServiceProvider extends ServiceProvider
{

    /**
     * The event listener mappings for package.
     *
     * @var array
     */
    protected $listen = [
        // 'Congraph\Eav\Events\AttributeSets\AfterAttributeSetFetch' => [
        // 	'Congraph\Eav\Handlers\Events\AttributeSets\AfterAttributeSetFetchHandler',
        // ],
    ];


    /**
     * Boot
     *
     * @return void
     */
    public function boot()
    {
        $this->mapCommandHandlers();
    }


    /**
     * Register
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommandHandlers();
    }

    /**
     * Maps Command Handlers
     *
     * @return void
     */
    public function mapCommandHandlers()
    {
        $mappings = [
            
            // Entities
            'Congraph\EntityElastic\Commands\Entities\EntityFetchCommand' =>
                'Congraph\EntityElastic\Handlers\Commands\Entities\EntityFetchHandler@handle',
            'Congraph\EntityElastic\Commands\Entities\EntityGetCommand' =>
                'Congraph\EntityElastic\Handlers\Commands\Entities\EntityGetHandler@handle',
            
        ];

        $this->app->make('Illuminate\Contracts\Bus\Dispatcher')->maps($mappings);
    }

    /**
     * Registers Command Handlers
     *
     * @return void
     */
    public function registerCommandHandlers()
    {

        // Entities
        $this->app->bind('Congraph\EntityElastic\Handlers\Commands\Entities\EntityFetchHandler', function ($app) {
            return new EntityFetchHandler($app->make('Congraph\EntityElastic\Repositories\EntityRepositoryContract'));
        });
        $this->app->bind('Congraph\EntityElastic\Handlers\Commands\Entities\EntityGetHandler', function ($app) {
            return new EntityGetHandler($app->make('Congraph\EntityElastic\Repositories\EntityRepositoryContract'));
        });
    }


    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            
            'Congraph\EntityElastic\Handlers\Commands\Entities\EntityFetchHandler',
            'Congraph\EntityElastic\Handlers\Commands\Entities\EntityGetHandler',
        ];
    }
}
