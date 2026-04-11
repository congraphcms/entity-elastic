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

use Congraph\EntityElastic\Fields\FieldHandlerFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container;
use OpenSearch\Client;

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

		$app = $this->app;

        $app->singleton('cb.elastic.factory', function($app) {
            return new ESFactory();
        });

		$app->singleton('cb.elastic', function($app) {
            return new ESManager($app, $app['cb.elastic.factory']);
        });

        $app->alias('cb.elastic', ESManager::class);

        $app->singleton(Client::class, function(Container $app) {
            return $app->make('cb.elastic')->connection();
        });



		$this->app->singleton(EntityFormater::class, function($app) {
			return new EntityFormater(
				$app->make(FieldHandlerFactory::class),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
	}

}
