<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Commands;

use Illuminate\Support\ServiceProvider;

use Congraph\EntityElastic\Commands\Entities\EntityFetchCommand;
use Congraph\EntityElastic\Commands\Entities\EntityGetCommand;

/**
 * CommandsServiceProvider service provider for commands
 * 
 * It will register all commands to app container
 * 
 * @uses   		Illuminate\Support\ServiceProvider
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class CommandsServiceProvider extends ServiceProvider {

	/**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
	protected $defer = true;


	/**
	* Register
	* 
	* @return void
	*/
	public function register() {
		$this->registerCommands();
	}

	/**
	* Register Command Handlers
	*
	* @return void
	*/
	public function registerCommands() {

		$this->app->bind(EntityFetchCommand::class, function($app){
			return new EntityFetchCommand($app->make('Congraph\EntityElastic\Repositories\EntityRepository'));
		});

		$this->app->bind(EntityGetCommand::class, function($app){
			return new EntityGetCommand($app->make('Congraph\EntityElastic\Repositories\EntityRepository'));
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
			// EAV Elastic
			'Congraph\EntityElastic\Commands\Entities\EntityFetchCommand',
			'Congraph\EntityElastic\Commands\Entities\EntityGetCommand'
		];
	}
}