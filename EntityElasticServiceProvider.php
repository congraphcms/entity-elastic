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
 * EntityElasticServiceProvider service provider for entity-elastic package
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
	protected function registerServiceProviders() {

        if(!config('cb.eav.using_elastic')) {
            return;
        }

		// Fields
		// -----------------------------------------------------------------------------
		$this->app->register('Congraph\EntityElastic\Fields\FieldsServiceProvider');

		// Services
		// -----------------------------------------------------------------------------
		$this->app->register('Congraph\EntityElastic\Services\ServicesServiceProvider');

		// Validators
		// -----------------------------------------------------------------------------
		$this->app->register('Congraph\EntityElastic\Validators\ValidatorsServiceProvider');

		// Repositories
		// -----------------------------------------------------------------------------
		$this->app->register('Congraph\EntityElastic\Repositories\RepositoriesServiceProvider');

		// Commands
		// -----------------------------------------------------------------------------
		$this->app->register('Congraph\EntityElastic\Commands\CommandsServiceProvider');




	}

}
