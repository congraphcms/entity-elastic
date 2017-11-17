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
			'--realpath' => realpath(__DIR__.'/../../vendor/Cookbook/Eav/database/migrations'),
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
			'Cookbook\Workflows\WorkflowsServiceProvider',
			'Cookbook\EntityElastic\EntityElasticServiceProvider'
		];
	}

	public function testTheTest()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

	}

	public function testCreateEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'locale' => 'en_US',
			'fields' => [
				'test_compound_text1_attribute' => 'test1',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'invalid'
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'test1',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'test1 test2',
			]
		], $array);
	}

	public function testCreateLocalizedEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'locale' => 'en_US',
			'fields' => [
				'test_compound_text1_attribute' => 'test1',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_localized_text_attribute' => 'test3',
				'test_compound_attribute' => 'invalid',
				'test_localized_compound_attribute' => 'invalid'
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'test1',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'test1 test2',
				'test_compound_localized_text_attribute' => 'test3',
				'test_localized_compound_attribute' => 'test1 test3',
			]
		], $array);


		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'test1',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_localized_text_attribute' => [
					'en_US' => 'test3-en',
					'fr_FR' => 'test3-fr'
				],
				'test_compound_attribute' => 'invalid',
				'test_localized_compound_attribute' => [
					'en_US' => 'invalid-en',
					'fr_FR' => 'invalid-fr'
				]
			]
		];

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'test1',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'test1 test2',
				'test_compound_localized_text_attribute' => [
					'en_US' => 'test3-en',
					'fr_FR' => 'test3-fr'
				],
				'test_localized_compound_attribute' => [
					'en_US' => 'test1 test3-en',
					'fr_FR' => 'test1 test3-fr'
				],
			]
		], $array);

	}

	public function testUpdateEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'locale' => 'en_US',
			'fields' => [
				'test_compound_text1_attribute' => 'test1',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'invalid'
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Cookbook\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);
		

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'test1',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'test1 test2',
			]
		], $array);



		$params = [
			'locale' => 'en_US',
			'fields' => [
				'test_compound_text1_attribute' => 'changed'
			]
		];

		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Cookbook\Core\Repositories\Model);
		$array = $result->toArray();
		$this->d->dump($result->toArray());

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'changed',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'changed test2',
			]
		], $array);
		
	}
}