<?php

use Congraph\Core\Exceptions\ValidationException;
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

class AsciifoldingTest extends Orchestra\Testbench\TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->artisan('migrate', [
			'--database' => 'testbench',
			'--realpath' => realpath(__DIR__.'/../../vendor/Congraph/Eav/database/migrations'),
		]);

		$this->artisan('migrate', [
			'--database' => 'testbench',
			'--realpath' => realpath(__DIR__.'/../../vendor/Congraph/Filesystem/database/migrations'),
		]);

		$this->artisan('migrate', [
			'--database' => 'testbench',
			'--realpath' => realpath(__DIR__.'/../../vendor/Congraph/Locales/database/migrations'),
		]);

		$this->artisan('migrate', [
			'--database' => 'testbench',
			'--realpath' => realpath(__DIR__.'/../../vendor/Congraph/Workflows/database/migrations'),
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
			'database'	=> 'congraph_testbench',
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
			'Congraph\Core\CoreServiceProvider',
			'Congraph\Locales\LocalesServiceProvider',
			'Congraph\Eav\EavServiceProvider',
			'Congraph\Filesystem\FilesystemServiceProvider',
			'Congraph\Workflows\WorkflowsServiceProvider',
			'Congraph\EntityElastic\EntityElasticServiceProvider'
		];
	}

	public function testTheTest()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

	}

	public function testAsciifoldingSearch()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();


		$params = [
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'locale' => 'en_US',
			'fields' => [
				'attribute1' => 'Đoković opet prvak mastersa',
				'attribute2' => 'Šljivančanin obećao plate',
				'attribute3' => 'Žarka Zrenjanina 25'
			]
		];

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);

		$params = [
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'locale' => 'en_US',
			'fields' => [
				'attribute1' => 'Djokovic opet prvak mastersa',
				'attribute2' => 'Sljivancanin obecao plate',
				'attribute3' => 'Zarka Zrenjanina 25'
			]
		];

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);


		$result = $repo->get(['s' => 'Đoković']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'Djoković']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'đoković']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'djoković']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'Šljivančanin']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'Šljivancanin']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'Sljivancanin']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'šljivančanin']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'šljivancanin']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'sljivancanin']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'žarka zrenjanina']);
		$this->assertEquals(2, count($result));

		$result = $repo->get(['s' => 'zarka zrenjanina']);
		$this->assertEquals(2, count($result));
	}

}
