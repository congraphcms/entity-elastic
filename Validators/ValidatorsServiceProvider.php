<?php
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Validators;

use Illuminate\Support\ServiceProvider;

use Cookbook\EntityElastic\Validators\Entities\EntityFetchValidator;
use Cookbook\EntityElastic\Validators\Entities\EntityGetValidator;

/**
 * ValidatorsServiceProvider service provider for validators
 * 
 * It will register all validators to app container
 * 
 * @uses   		Illuminate\Support\ServiceProvider
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	cookbook/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class ValidatorsServiceProvider extends ServiceProvider {

	/**
	 * Boot
	 * 
	 * @return void
	 */
	public function boot() {
		$this->mapValidators();
	}


	/**
	 * Register
	 * 
	 * @return void
	 */
	public function register() {
		$this->registerValidators();
	}

	/**
	 * Maps Validators
	 *
	 * @return void
	 */
	public function mapValidators() {
		
		$mappings = [
			
			// Entities
			'Cookbook\EntityElastic\Commands\Entities\EntityFetchCommand' => 
				'Cookbook\EntityElastic\Validators\Entities\EntityFetchValidator@validate',
			'Cookbook\EntityElastic\Commands\Entities\EntityGetCommand' => 
				'Cookbook\EntityElastic\Validators\Entities\EntityGetValidator@validate',
		];

		$this->app->make('Illuminate\Contracts\Bus\Dispatcher')->mapValidators($mappings);
	}

	/**
	 * Registers Command Handlers
	 *
	 * @return void
	 */
	public function registerValidators() {


		// Entities
		
		$this->app->bind('Cookbook\EntityElastic\Validators\Entities\EntityFetchValidator', function($app){
			return new EntityFetchValidator(
				$app->make('Cookbook\EntityElastic\Repositories\EntityRepository')
			);
		});
		$this->app->bind('Cookbook\EntityElastic\Validators\Entities\EntityGetValidator', function($app){
			return new EntityGetValidator(
				$app->make('Cookbook\Eav\Managers\AttributeManager'),
				$app->make('Cookbook\Contracts\Eav\FieldValidatorFactoryContract')
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

			// Entities
			'Cookbook\EntityElastic\Validators\Entities\EntityFetchValidator',
			'Cookbook\EntityElastic\Validators\Entities\EntityGetValidator'

		];
	}
}