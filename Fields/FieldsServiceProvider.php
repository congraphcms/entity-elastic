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

use Congraph\EntityElastic\Fields\Asset\AssetFieldHandler;
use Congraph\EntityElastic\Fields\Boolean\BooleanFieldHandler;
use Congraph\EntityElastic\Fields\Datetime\DatetimeFieldHandler;
use Congraph\EntityElastic\Fields\Decimal\DecimalFieldHandler;
use Congraph\EntityElastic\Fields\Integer\IntegerFieldHandler;
use Congraph\EntityElastic\Fields\Location\LocationFieldHandler;
use Congraph\EntityElastic\Fields\Node\NodeFieldHandler;
use Congraph\EntityElastic\Fields\Relation\RelationFieldHandler;
use Congraph\EntityElastic\Fields\Select\SelectFieldHandler;
use Congraph\EntityElastic\Fields\Text\TextFieldHandler;
use Congraph\EntityElastic\Fields\Textarea\TextareaFieldHandler;
use Congraph\EntityElastic\Repositories\EntityRepository as ElasticEntityRepository;
use Congraph\EntityElastic\Services\EntityFormater;
use Congraph\Eav\Managers\AttributeManager;
use Congraph\Eav\Repositories\EntityRepository;
use Illuminate\Support\ServiceProvider;
use OpenSearch\Client;

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
			->singleton(FieldHandlerFactory::class, function ($app) {
				return new FieldHandlerFactory(
					$app['app'],
					$app->make(AttributeManager::class)
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
		$this->app->singleton(AssetFieldHandler::class, function ($app) {
			return new AssetFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class)
			);
		});
		$this->app->singleton(BooleanFieldHandler::class, function ($app) {
			return new BooleanFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class)
			);
		});
		$this->app->singleton(DatetimeFieldHandler::class, function ($app) {
			return new DatetimeFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class)
			);
		});
		$this->app->singleton(DecimalFieldHandler::class, function ($app) {
			return new DecimalFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class)
			);
		});
		$this->app->singleton(IntegerFieldHandler::class, function ($app) {
			return new IntegerFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class)
			);
		});
		$this->app->singleton(RelationFieldHandler::class, function ($app) {
			return new RelationFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class),
				$app->make(EntityRepository::class),
				$app->make(ElasticEntityRepository::class)
			);
		});
		$this->app->singleton(SelectFieldHandler::class, function ($app) {
			return new SelectFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class)
			);
		});
		$this->app->singleton(TextFieldHandler::class, function ($app) {
			return new TextFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class)
			);
		});
		$this->app->singleton(TextareaFieldHandler::class, function ($app) {
			return new TextareaFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class)
			);
		});
		$this->app->singleton(LocationFieldHandler::class, function ($app) {
			return new LocationFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class)
			);
		});
		$this->app->singleton(NodeFieldHandler::class, function ($app) {
			return new NodeFieldHandler(
				$app->make(Client::class),
				$app->make(AttributeManager::class),
				$app->make(EntityFormater::class),
				$app->make(EntityRepository::class),
				$app->make(ElasticEntityRepository::class)
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
			FieldHandlerFactory::class
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
