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

use Congraph\Contracts\Core\ObjectResolverContract;
use Congraph\EntityElastic\Fields\FieldHandlerFactory;
use Congraph\EntityElastic\Services\EntityFormater;
use Congraph\Eav\Managers\AttributeManager;
use Illuminate\Support\ServiceProvider;
use OpenSearch\Client;

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
        $this->app->singleton(EntityRepository::class, function ($app) {
            return new EntityRepository(
                $app->make(Client::class),
                $app->make(FieldHandlerFactory::class),
                $app->make(AttributeManager::class),
                $app->make(EntityFormater::class)
            );
        });

        $this->app->alias(
            EntityRepository::class,
            EntityRepositoryContract::class
        );

        $this->app->alias(
            EntityRepository::class,
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

        $this->app->make(ObjectResolverContract::class)->maps($mappings);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            EntityRepository::class,
            EntityRepositoryContract::class,
            'Congraph\EntityElastic\Repositories\EntityRepositoryContract'
        ];
    }
}
