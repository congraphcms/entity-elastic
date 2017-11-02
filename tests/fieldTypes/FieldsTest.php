<?php

use Cookbook\Core\Exceptions\ValidationException;
use Illuminate\Support\Debug\Dumper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Elasticsearch\ClientBuilder;

require_once(__DIR__ . '/../database/seeders/EavDbSeeder.php');
require_once(__DIR__ . '/../database/seeders/LocaleDbSeeder.php');
require_once(__DIR__ . '/../database/seeders/FileDbSeeder.php');
require_once(__DIR__ . '/../database/seeders/WorkflowDbSeeder.php');
require_once(__DIR__ . '/../database/seeders/ClearDB.php');
require_once(__DIR__ . '/../database/seeders/ElasticIndexSeeder.php');

class FieldsTest extends Orchestra\Testbench\TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->artisan('migrate', [
			'--database' => 'testbench',
			'--realpath' => realpath(__DIR__.'/../../database/migrations'),
		]);

		$this->artisan('migrate', [
			'--database' => 'testbench',
			'--realpath' => realpath(__DIR__.'/../../vendor/Cookbook/Filesystem/database/migrations'),
		]);

		$this->artisan('migrate', [
			'--database' => 'testbench',
			'--realpath' => realpath(__DIR__.'/../../vendor/Cookbook/Locales/database/migrations'),
		]);

		$this->artisan('migrate', [
			'--database' => 'testbench',
			'--realpath' => realpath(__DIR__.'/../../vendor/Cookbook/Workflows/database/migrations'),
		]);

		$this->artisan('db:seed', [
			'--class' => 'EavDbSeeder'
		]);

		$this->artisan('db:seed', [
			'--class' => 'LocaleDbSeeder'
		]);

		$this->artisan('db:seed', [
			'--class' => 'FileDbSeeder'
		]);

		$this->artisan('db:seed', [
			'--class' => 'WorkflowDbSeeder'
		]);

		$this->d = new Dumper();

		$elasticClientBuilder = new ClientBuilder();

		$hosts = Config::get('cb.elastic.hosts');

        $client = $elasticClientBuilder->create()
                                            ->setHosts($hosts)
                                            ->build();

		$this->elasticSeeder = new ElasticIndexSeeder($client);

		$this->elasticSeeder->up();

	}

	public function tearDown()
	{
		$this->artisan('db:seed', [
			'--class' => 'ClearDB'
		]);
		DB::disconnect();

		$this->elasticSeeder->down();

		parent::tearDown();
	}

	/**
	 * Define environment setup.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 *
	 * @return void
	 */
	protected function getEnvironmentSetUp($app)
	{
		$app['config']->set('database.default', 'testbench');
		$app['config']->set('database.connections.testbench', [
			'driver'   	=> 'mysql',
			'host'      => '127.0.0.1',
			'port'		=> '3306',
			'database'	=> 'cookbook_testbench',
			'username'  => 'root',
			'password'  => '',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		]);

		$app['config']->set('cache.default', 'file');

		$app['config']->set('cache.stores.file', [
			'driver'	=> 'file',
			'path'   	=> realpath(__DIR__ . '/../storage/cache/'),
		]);

		$app['config']->set('filesystems.default', 'local');

		$app['config']->set('filesystems.disks.local', [
			'driver'	=> 'local',
			'root'   	=> realpath(__DIR__ . '/../storage/'),
		]);
	}

	protected function getPackageProviders($app)
	{
		return [
			'Cookbook\Core\CoreServiceProvider',
			'Cookbook\Locales\LocalesServiceProvider',
			'Cookbook\Eav\EavServiceProvider',
			'Cookbook\Filesystem\FilesystemServiceProvider',
			'Cookbook\Workflows\WorkflowsServiceProvider'
		];
	}

	public function testTheTest()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

	}

	public function testTextField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_text_attribute' => 'abc'
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_text_attribute' => 'abc'
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_text_attribute' => 'changed'
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_text_attribute' => 'changed'
			]
		], $array);
	}

	public function testTagsField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_tags_attribute' => ['abc', 'def']
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_tags_attribute' => ['abc', 'def']
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_tags_attribute' => ['changed', 'field', 'abc']
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_tags_attribute' => ['changed', 'field', 'abc']
			]
		], $array);
	}

	public function testSelectField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		
		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_select_attribute' => 'option1'
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_select_attribute' => 'option1'
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_select_attribute' => 'option2'
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_select_attribute' => 'option2'
			]
		], $array);

		// update remove field
		$params = [
			'fields' => [
				'test_select_attribute' => null
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_select_attribute' => null
			]
		], $array);
	}

	public function testMultiselectField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		
		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_multiselect_attribute' => ['option1']
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_multiselect_attribute' => ['option1']
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_multiselect_attribute' => ['option1', 'option2']
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_multiselect_attribute' => ['option1', 'option2']
			]
		], $array);

		// update remove field
		$params = [
			'fields' => [
				'test_multiselect_attribute' => null
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_multiselect_attribute' => null
			]
		], $array);
	}

	public function testIntegerField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_integer_attribute' => 11
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_integer_attribute' => 11
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_integer_attribute' => 113
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_integer_attribute' => 113
			]
		], $array);
	}

	public function testDecimalField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_decimal_attribute' => 11.1
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_decimal_attribute' => 11.1
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_decimal_attribute' => 113.3
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_decimal_attribute' => 113.3
			]
		], $array);
	}

	public function testBooleanField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_boolean_attribute' => true
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_boolean_attribute' => true
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_boolean_attribute' => false
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_boolean_attribute' => false
			]
		], $array);
	}

	public function testAssetField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_asset_attribute' => ['id' => 1, 'type' => 'file']
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_asset_attribute' => ['id' => 1, 'type' => 'file']
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_asset_attribute' => ['id' => 2, 'type' => 'file']
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_asset_attribute' => ['id' => 2, 'type' => 'file']
			]
		], $array);

		// update clear field
		$params = [
			'fields' => [
				'test_asset_attribute' => null
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_asset_attribute' => null
			]
		], $array);

		// file remove handler
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_asset_attribute' => ['id' => 1, 'type' => 'file']
			]
		];

		$entityWithFile = $repo->create($params);
		$repo->refreshIndex();

		$bus = $app->make('Illuminate\Contracts\Bus\Dispatcher');

		$result = $bus->dispatch( new Cookbook\Filesystem\Commands\Files\FileDeleteCommand([], 1));
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithFile->id);
		$this->assertFalse(isset($changedEntity->fields->test_asset_attribute));

		$result = $bus->dispatch( new Cookbook\Filesystem\Commands\Files\FileDeleteCommand([], 2));
		$repo->refreshIndex();
	}

	public function testAssetCollectionField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_assetcollection_attribute' => [
					['id' => 1, 'type' => 'file'],
					['id' => 2, 'type' => 'file']
				]
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_assetcollection_attribute' => [
					['id' => 1, 'type' => 'file'],
					['id' => 2, 'type' => 'file']
				]
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_assetcollection_attribute' => [
					['id' => 3, 'type' => 'file']
				]
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_assetcollection_attribute' => [
					['id' => 3, 'type' => 'file']
				]
			]
		], $array);

		// update clear field
		$params = [
			'fields' => [
				'test_assetcollection_attribute' => null
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_assetcollection_attribute' => null
			]
		], $array);

		// file remove handler
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_assetcollection_attribute' => [
					['id' => 1, 'type' => 'file'],
					['id' => 2, 'type' => 'file']
				]
			]
		];

		$entityWithFile = $repo->create($params);
		$repo->refreshIndex();

		$bus = $app->make('Illuminate\Contracts\Bus\Dispatcher');

		$result = $bus->dispatch( new Cookbook\Filesystem\Commands\Files\FileDeleteCommand([], 1));
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithFile->id);
		$array = $changedEntity->toArray();
		// $this->d->dump($array);
		$this->assertTrue(count($array['fields']['test_assetcollection_attribute']) > 0);
		foreach ($array['fields']['test_assetcollection_attribute'] as $value)
		{
			$this->assertNotEquals(1, $value['id']);
		}

		$result = $bus->dispatch( new Cookbook\Filesystem\Commands\Files\FileDeleteCommand([], 2));
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithFile->id);
		$array = $changedEntity->toArray();

		$this->assertTrue(count($array['fields']['test_assetcollection_attribute']) == 0);
	}

	public function testRelationField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_relation_attribute' => ['id' => 1, 'type' => 'entity']
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_relation_attribute' => ['id' => 1, 'type' => 'entity']
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_relation_attribute' => ['id' => 2, 'type' => 'entity']
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_relation_attribute' => ['id' => 2, 'type' => 'entity']
			]
		], $array);

		// update clear field
		$params = [
			'fields' => [
				'test_relation_attribute' => null
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_relation_attribute' => null
			]
		], $array);

		// entity remove handler
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_relation_attribute' => ['id' => 1, 'type' => 'entity']
			]
		];

		$entityWithRelation = $repo->create($params);
		$repo->refreshIndex();

		$bus = $app->make('Illuminate\Contracts\Bus\Dispatcher');

		$result = $bus->dispatch( new Cookbook\Eav\Commands\Entities\EntityDeleteCommand([], 1));
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithRelation->id);
		$this->assertFalse(isset($changedEntity->fields->test_relation_attribute));

		$result = $bus->dispatch( new Cookbook\Eav\Commands\Entities\EntityDeleteCommand([], 2));
		$repo->refreshIndex();
	}

	public function testRelationCollectionField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_relationcollection_attribute' => [
					['id' => 1, 'type' => 'entity'],
					['id' => 2, 'type' => 'entity']
				]
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\Eav\Repositories\EntityElasticRepository');

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_relationcollection_attribute' => [
					['id' => 1, 'type' => 'entity'],
					['id' => 2, 'type' => 'entity']
				]
			]
		], $array);

		// update field
		$params = [
			'fields' => [
				'test_relationcollection_attribute' => [
					['id' => 3, 'type' => 'entity']
				]
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_relationcollection_attribute' => [
					['id' => 3, 'type' => 'entity']
				]
			]
		], $array);

		// update clear field
		$params = [
			'fields' => [
				'test_relationcollection_attribute' => null
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_relationcollection_attribute' => null
			]
		], $array);

		// file remove handler
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_relationcollection_attribute' => [
					['id' => 1, 'type' => 'entity'],
					['id' => 2, 'type' => 'entity']
				]
			]
		];

		$entityWithRelation = $repo->create($params);
		$repo->refreshIndex();

		$bus = $app->make('Illuminate\Contracts\Bus\Dispatcher');

		$result = $bus->dispatch( new Cookbook\Eav\Commands\Entities\EntityDeleteCommand([], 1));
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithRelation->id);
		$array = $changedEntity->toArray();
		// $this->d->dump($array);
		$this->assertTrue(count($array['fields']['test_relationcollection_attribute']) > 0);
		foreach ($array['fields']['test_relationcollection_attribute'] as $value)
		{
			$this->assertNotEquals(1, $value['id']);
		}

		$result = $bus->dispatch( new Cookbook\Eav\Commands\Entities\EntityDeleteCommand([], 2));
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithRelation->id);
		$array = $changedEntity->toArray();

		$this->assertTrue(count($array['fields']['test_relationcollection_attribute']) == 0);
	}
}