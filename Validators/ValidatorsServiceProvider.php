<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Validators;

use Illuminate\Support\ServiceProvider;

use Congraph\EntityElastic\Validators\Entities\EntityFetchValidator;
use Congraph\EntityElastic\Validators\Entities\EntityGetValidator;

/**
 * ValidatorsServiceProvider service provider for validators
 * 
 * It will register all validators to app container
 * 
 * @uses   		Illuminate\Support\ServiceProvider
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
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
			'Congraph\EntityElastic\Commands\Entities\EntityFetchCommand' => 
				'Congraph\EntityElastic\Validators\Entities\EntityFetchValidator@validate',
			'Congraph\EntityElastic\Commands\Entities\EntityGetCommand' => 
				'Congraph\EntityElastic\Validators\Entities\EntityGetValidator@validate',
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
		
		$this->app->bind('Congraph\EntityElastic\Validators\Entities\EntityFetchValidator', function($app){
			return new EntityFetchValidator(
				$app->make('Congraph\EntityElastic\Repositories\EntityRepository')
			);
		});
		$this->app->bind('Congraph\EntityElastic\Validators\Entities\EntityGetValidator', function($app){
			return new EntityGetValidator(
				$app->make('Congraph\Eav\Managers\AttributeManager'),
				$app->make('Congraph\Contracts\Eav\FieldValidatorFactoryContract')
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
			'Congraph\EntityElastic\Validators\Entities\EntityFetchValidator',
			'Congraph\EntityElastic\Validators\Entities\EntityGetValidator'

		];
	}
}