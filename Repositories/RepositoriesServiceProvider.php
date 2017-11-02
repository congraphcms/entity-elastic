<?php
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Repositories;

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
 * @package 	cookbook/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class RepositoriesServiceProvider extends ServiceProvider {

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
		// $this->app['events']->listen('cb.after.entity.type.create', 'Cookbook\Eav\Repositories\EntityElasticRepository@onEntityTypeCreated');
		// $this->app['events']->listen('cb.before.entity.type.update', 'Cookbook\Eav\Repositories\EntityElasticRepository@onBeforeEntityTypeUpdated');
		// $this->app['events']->listen('cb.after.entity.type.update', 'Cookbook\Eav\Repositories\EntityElasticRepository@onEntityTypeUpdated');
		// $this->app['events']->listen('cb.before.entity.type.delete', 'Cookbook\Eav\Repositories\EntityElasticRepository@onBeforeEntityTypeDeleted');
		// $this->app['events']->listen('cb.after.entity.type.delete', 'Cookbook\Eav\Repositories\EntityElasticRepository@onEntityTypeDeleted');
	}

	/**
	 * Register Repositories
	 *
	 * @return void
	 */
	public function registerRepositories() {
		$this->app->singleton('Cookbook\EntityElastic\Repositories\EntityRepository', function($app) {
			// var_dump('Contract for attribute repository resolving...');
			return new EntityRepository(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\ElasticFields\FieldHandlerFactory'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
			);
		});
	}

	/**
	 * Map repositories to object resolver
	 *
	 * @return void
	 */
	public function mapObjectResolvers() {
		$mappings = [
			// 'entity' => 'Cookbook\EntityElastic\Repositories\EntityElasticRepository',
		];

		$this->app->make('Cookbook\Contracts\Core\ObjectResolverContract')->maps($mappings);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'Cookbook\EntityElastic\Repositories\EntityRepository',
		];
	}


}