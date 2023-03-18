<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Repositories;

use Illuminate\Support\ServiceProvider;

/**
 * RepositoriesServiceProvider service provider for managers
 *
 * It will register all manager to app container
 *
 * @uses   		Illuminate\Support\ServiceProvider
 *
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class RepositoriesServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Boot
     * @return void
     */
    public function boot()
    {
        $this->mapObjectResolvers();
    }


    /**
     * Register
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepositories();
        $this->registerListeners();
    }

    /**
     * Register Event Listeners
     *
     * @return void
     */
    protected function registerListeners()
    {
        $this->app['events']->listen('cb.after.entity.create', 'Congraph\EntityElastic\Repositories\EntityRepository@onEntityCreated', 100);
        $this->app['events']->listen('cb.after.entity.update', 'Congraph\EntityElastic\Repositories\EntityRepository@onEntityUpdated', 100);
        $this->app['events']->listen('cb.after.entity.delete', 'Congraph\EntityElastic\Repositories\EntityRepository@onEntityDeleted', 100);
        $this->app['events']->listen('cb.after.attribute.delete', 'Congraph\EntityElastic\Repositories\EntityRepository@onAttributeDeleted', 100);
        $this->app['events']->listen('cb.after.attribute.set.delete', 'Congraph\EntityElastic\Repositories\EntityRepository@onAttributeSetDeleted', 100);
        $this->app['events']->listen('cb.after.entity.type.delete', 'Congraph\EntityElastic\Repositories\EntityRepository@onEntityTypeDeleted', 100);
    }

    /**
     * Register Repositories
     *
     * @return void
     */
    public function registerRepositories()
    {
        $this->app->singleton('Congraph\EntityElastic\Repositories\EntityRepository', function ($app) {
            // var_dump('Contract for attribute repository resolving...');
            return new EntityRepository(
                $app->make('Elasticsearch\Client'),
                $app->make('Congraph\EntityElastic\Fields\FieldHandlerFactory'),
                $app->make('Congraph\Eav\Managers\AttributeManager'),
                $app->make('Congraph\EntityElastic\Services\EntityFormater')
            );
        });

        $this->app->alias(
            'Congraph\EntityElastic\Repositories\EntityRepository',
            'Congraph\EntityElastic\Repositories\EntityRepositoryContract'
        );
    }

    /**
     * Map repositories to object resolver
     *
     * @return void
     */
    public function mapObjectResolvers()
    {
        $mappings = [
            // 'entity' => 'Congraph\EntityElastic\Repositories\EntityElasticRepository',
        ];

        $this->app->make('Congraph\Contracts\Core\ObjectResolverContract')->maps($mappings);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'Congraph\EntityElastic\Repositories\EntityRepository',
            'Congraph\EntityElastic\Repositories\EntityRepositoryContract'
        ];
    }
}
