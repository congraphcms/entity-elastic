<?php
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Handlers;

use Illuminate\Support\ServiceProvider;

use Cookbook\EntityElastic\Handlers\Commands\Entities\EntityFetchHandler;
use Cookbook\EntityElastic\Handlers\Commands\Entities\EntityGetHandler;

/**
 * HandlersServiceProvider service provider for handlers
 * 
 * It will register all handlers to app container
 * 
 * @uses   		Illuminate\Support\ServiceProvider
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	cookbook/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class HandlersServiceProvider extends ServiceProvider {

	/**
	 * The event listener mappings for package.
	 *
	 * @var array
	 */
	protected $listen = [
		// 'Cookbook\Eav\Events\AttributeSets\AfterAttributeSetFetch' => [
		// 	'Cookbook\Eav\Handlers\Events\AttributeSets\AfterAttributeSetFetchHandler',
		// ],
	];


	/**
	 * Boot
	 * 
	 * @return void
	 */
	public function boot() {
		$this->mapCommandHandlers();
	}


	/**
	 * Register
	 * 
	 * @return void
	 */
	public function register() {
		$this->registerCommandHandlers();
	}

	/**
	 * Maps Command Handlers
	 *
	 * @return void
	 */
	public function mapCommandHandlers() {
		
		$mappings = [
			
			// Entities
			'Cookbook\EntityElastic\Commands\Entities\EntityFetchCommand' => 
				'Cookbook\EntityElastic\Handlers\Commands\Entities\EntityFetchHandler@handle',
			'Cookbook\EntityElastic\Commands\Entities\EntityGetCommand' => 
				'Cookbook\EntityElastic\Handlers\Commands\Entities\EntityGetHandler@handle',
			
		];

		$this->app->make('Illuminate\Contracts\Bus\Dispatcher')->maps($mappings);
	}

	/**
	 * Registers Command Handlers
	 *
	 * @return void
	 */
	public function registerCommandHandlers() {

		// Entities
		$this->app->bind('Cookbook\EntityElastic\Handlers\Commands\Entities\EntityFetchHandler', function($app){
			return new EntityFetchHandler($app->make('Cookbook\EntityElastic\Repositories\EntityRepository'));
		});
		$this->app->bind('Cookbook\EntityElastic\Handlers\Commands\Entities\EntityGetHandler', function($app){
			return new EntityGetHandler($app->make('Cookbook\EntityElastic\Repositories\EntityRepository'));
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
			
			'Cookbook\EntityElastic\Handlers\Commands\Entities\EntityFetchHandler',
			'Cookbook\EntityElastic\Handlers\Commands\Entities\EntityGetHandler',
		];
	}
}