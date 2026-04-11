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

use Congraph\Contracts\Eav\FieldValidatorFactoryContract;
use Congraph\EntityElastic\Commands\Entities\EntityFetchCommand;
use Congraph\EntityElastic\Commands\Entities\EntityGetCommand;
use Congraph\EntityElastic\Repositories\EntityRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Bus\Dispatcher;
use Congraph\Eav\Managers\AttributeManager;

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
			EntityFetchCommand::class => EntityFetchValidator::class . '@validate',
			EntityGetCommand::class => EntityGetValidator::class . '@validate',
		];

		$this->app->make(Dispatcher::class)->mapValidators($mappings);
	}

	/**
	 * Registers Command Handlers
	 *
	 * @return void
	 */
	public function registerValidators() {


		// Entities
		
		$this->app->bind(EntityFetchValidator::class, function($app){
			return new EntityFetchValidator(
				$app->make(EntityRepository::class)
			);
		});
		$this->app->bind(EntityGetValidator::class, function($app){
			return new EntityGetValidator(
				$app->make(AttributeManager::class),
				$app->make(FieldValidatorFactoryContract::class)
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
			EntityFetchValidator::class,
			EntityGetValidator::class,
		];
	}
}
