<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Fields;

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
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class FieldsServiceProvider extends ServiceProvider
{

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
	public function register()
	{
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
		$this->app['events']->listen('cb.after.file.delete', 'Congraph\EntityElastic\Fields\Asset\AssetFieldHandler@onFileDelete', 10);
		$this->app['events']->listen('cb.after.entity.delete', 'Congraph\EntityElastic\Fields\Relation\RelationFieldHandler@onEntityDelete', 10);
		$this->app['events']->listen('cb.after.entity.update', 'Congraph\EntityElastic\Fields\Node\NodeFieldHandler@onEntityUpdate', 10);
		$this->app['events']->listen('cb.after.entity.delete', 'Congraph\EntityElastic\Fields\Node\NodeFieldHandler@onEntityDelete', 10);
		$this->app['events']->listen('cb.before.entity.update', 'Congraph\EntityElastic\Fields\Compound\CompoundFieldHandler@onBeforeEntityUpdate', 10);
		$this->app['events']->listen('cb.after.entity.update', 'Congraph\EntityElastic\Fields\Compound\CompoundFieldHandler@onAfterEntityUpdate', 10);
	}

	/**
	 * Register the AttributeHandlerFactory
	 *
	 * @return void
	 */
	protected function registerFactories()
	{
		$this->app
			->singleton('Congraph\EntityElastic\Fields\FieldHandlerFactory', function ($app) {
				return new FieldHandlerFactory(
					$app['app'],
					$app->make('Congraph\Eav\Managers\AttributeManager')
				);
			});
	}

	/**
	 * Register Field Handlers
	 *
	 * @return void
	 */
	protected function registerFieldHandlers()
	{
		$this->app->singleton('Congraph\EntityElastic\Fields\Asset\AssetFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Asset\AssetFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Congraph\EntityElastic\Fields\Boolean\BooleanFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Boolean\BooleanFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Congraph\EntityElastic\Fields\Datetime\DatetimeFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Datetime\DatetimeFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Congraph\EntityElastic\Fields\Decimal\DecimalFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Decimal\DecimalFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Congraph\EntityElastic\Fields\Integer\IntegerFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Integer\IntegerFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Congraph\EntityElastic\Fields\Relation\RelationFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Relation\RelationFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Congraph\EntityElastic\Fields\Select\SelectFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Select\SelectFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Congraph\EntityElastic\Fields\Text\TextFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Text\TextFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Congraph\EntityElastic\Fields\Textarea\TextareaFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Textarea\TextareaFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Congraph\EntityElastic\Fields\Location\LocationFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Location\LocationFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager')
			);
		});
		$this->app->singleton('Congraph\EntityElastic\Fields\Node\NodeFieldHandler', function ($app) {
			return new \Congraph\EntityElastic\Fields\Node\NodeFieldHandler(
				$app->make('Elasticsearch\ClientBuilder'),
				$app->make('Congraph\Eav\Managers\AttributeManager'),
				$app->make('Congraph\EntityElastic\Services\EntityFormater')
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
			'Congraph\EntityElastic\Fields\FieldHandlerFactory'
		];

		$field_types = $this->app['config']->get('cb.eav');

		if (! is_array($field_types)) {
			return $provides;
		}

		foreach ($field_types as $type => $settings) {
			if (isset($settings['elastic_handler'])) {
				$provides[] = $settings['elastic_handler'];
			}
		}


		return $provides;
	}
}
