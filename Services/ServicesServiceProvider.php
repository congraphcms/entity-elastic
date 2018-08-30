<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Services;

use Illuminate\Support\ServiceProvider;

/**
 * ServicesServiceProvider service provider for services
 * 
 * It will register all services to app container
 * 
 * @uses   		Illuminate\Support\ServiceProvider
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class ServicesServiceProvider extends ServiceProvider {

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
		// $this->mapObjectResolvers();
	}


	/**
	 * Register
	 * 
	 * @return void
	 */
	public function register()
	{
		$this->registerServices();
		$this->registerListeners();
		
	}

	/**
	 * Register Event Listeners
	 *
	 * @return void
	 */
	protected function registerListeners()
	{
		// $this->app['events']->listen('cb.after.entity.create', 'Congraph\EntityElastic\Repositories\EntityRepository@onEntityCreated');
	}

	/**
	 * Register Services
	 *
	 * @return void
	 */
	public function registerServices() {
		$this->app->singleton('Congraph\EntityElastic\Services\EntityFormater', function($app) {
			// var_dump('Contract for attribute repository resolving...');
			return new EntityFormater(
				$app->make('Congraph\EntityElastic\Fields\FieldHandlerFactory'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
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
			'Congraph\EntityElastic\Services\EntityFormater',
		];
	}


}