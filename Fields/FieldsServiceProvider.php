<?php
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Fields;

use Illuminate\Support\ServiceProvider;

/**
 * FieldsServiceProvider service provider for handlers
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
class FieldsServiceProvider extends ServiceProvider {

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
		$this->registerListeners();
	}

	/**
	* Register
	* 
	* @return void
	*/
	public function register() {
		$this->registerFactories();
		$this->registerFieldHandlers();
	}

	/**
	* Register Event Listeners
	*
	* @return void
	*/
	protected function registerListeners()
	{
		$this->app['events']->listen('cb.after.file.delete', 'Cookbook\EntityElastic\Fields\Asset\AssetFieldHandler@onFileDelete');
		$this->app['events']->listen('cb.after.entity.delete', 'Cookbook\EntityElastic\Fields\Relation\RelationFieldHandler@onEntityDelete');
		$this->app['events']->listen('cb.before.entity.update', 'Cookbook\EntityElastic\Fields\Compound\CompoundFieldHandler@onBeforeEntityUpdate');
		$this->app['events']->listen('cb.after.entity.update', 'Cookbook\EntityElastic\Fields\Compound\CompoundFieldHandler@onAfterEntityUpdate');
	}

	/**
	* Register the AttributeHandlerFactory
	*
	* @return void
	*/
	protected function registerFactories() {
		$this 	->app
				->singleton('Cookbook\EntityElastic\Fields\FieldHandlerFactory', function($app){
					return new FieldHandlerFactory(
						$app['app'],
						$app->make('Cookbook\Eav\Managers\AttributeManager')
					);
				});
	}

	/**
	* Register Field Handlers
	*
	* @return void
	*/
	protected function registerFieldHandlers() {

		$this->app->singleton('Cookbook\EntityElastic\Fields\Asset\AssetFieldHandler', function($app) {
			return new \Cookbook\EntityElastic\Fields\Asset\AssetFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Cookbook\EntityElastic\Fields\Boolean\BooleanFieldHandler', function($app) {
			return new \Cookbook\EntityElastic\Fields\Boolean\BooleanFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Cookbook\EntityElastic\Fields\Datetime\DatetimeFieldHandler', function($app) {
			return new \Cookbook\EntityElastic\Fields\Datetime\DatetimeFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Cookbook\EntityElastic\Fields\Decimal\DecimalFieldHandler', function($app) {
			return new \Cookbook\EntityElastic\Fields\Decimal\DecimalFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Cookbook\EntityElastic\Fields\Integer\IntegerFieldHandler', function($app) {
			return new \Cookbook\EntityElastic\Fields\Integer\IntegerFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Cookbook\EntityElastic\Fields\Relation\RelationFieldHandler', function($app) {
			return new \Cookbook\EntityElastic\Fields\Relation\RelationFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Cookbook\EntityElastic\Fields\Select\SelectFieldHandler', function($app) {
			return new \Cookbook\EntityElastic\Fields\Select\SelectFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Cookbook\EntityElastic\Fields\Text\TextFieldHandler', function($app) {
			return new \Cookbook\EntityElastic\Fields\Text\TextFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Cookbook\EntityElastic\Fields\Textarea\TextareaFieldHandler', function($app) {
			return new \Cookbook\EntityElastic\Fields\Textarea\TextareaFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Cookbook\EntityElastic\Fields\Location\LocationFieldHandler', function($app) {
			return new \Cookbook\EntityElastic\Fields\Location\LocationFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Cookbook\Eav\Managers\AttributeManager')
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
		$provides = [
			'Cookbook\EntityElastic\Fields\FieldHandlerFactory'
		];

		$field_types = $this->app['config']->get('cb.eav');

		if( ! is_array($field_types) )
		{
			return $provides;
		}

		foreach ($field_types as $type => $settings)
		{
			if( isset($settings['elastic_handler']) )
			{
				$provides[] = $settings['elastic_handler'];
			}		}


		return $provides;
	}
}