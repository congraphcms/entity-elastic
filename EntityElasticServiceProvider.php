<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic;

use Illuminate\Support\ServiceProvider;

/**
 * EavServiceProvider service provider for EAV package
 * 
 * It will register all manager to app container
 * 
 * @uses   		Illuminate\Support\ServiceProvider
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/eav
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class EntityElasticServiceProvider extends ServiceProvider {

	/**
	* Register
	* 
	* @return void
	*/
	public function register() {
		$this->mergeConfigFrom(realpath(__DIR__ . '/config/config.php'), 'cb.elastic');
		$this->mergeConfigFrom(realpath(__DIR__ . '/config/eav.php'), 'cb.eav');
		$this->registerServiceProviders();
	}

	/**
	 * Boot
	 * 
	 * @return void
	 */
	public function boot() {
		$this->publishes([
			__DIR__.'/config/config.php' => config_path('cb.elastic.php'),
			__DIR__.'/config/eav.php' => config_path('cb.eav.php'),
		]);
	}

	/**
	 * Register Service Providers for this package
	 * 
	 * @return void
	 */
	protected function registerServiceProviders(){

		// Fields
		// -----------------------------------------------------------------------------
		$this->app->register('Congraph\EntityElastic\Fields\FieldsServiceProvider');

		// Handlers
		// -----------------------------------------------------------------------------
		$this->app->register('Congraph\EntityElastic\Handlers\HandlersServiceProvider');

		// Validators
		// -----------------------------------------------------------------------------
		$this->app->register('Congraph\EntityElastic\Validators\ValidatorsServiceProvider');

		// Repositories
		// -----------------------------------------------------------------------------
		$this->app->register('Congraph\EntityElastic\Repositories\RepositoriesServiceProvider');

		// Services
		// -----------------------------------------------------------------------------
		$this->app->register('Congraph\EntityElastic\Services\ServicesServiceProvider');

		

		
	}

}