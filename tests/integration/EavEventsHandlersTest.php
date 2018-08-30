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

class EavEventsHandlersTest extends Orchestra\Testbench\TestCase
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

	public function testCreateEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$bus = $app->make('Illuminate\Contracts\Bus\Dispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');

		$params = [
			'entity_type' => ['id' => 1],
			'attribute_set' => ['id' => 1],
			'locale' => 'en_US',
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => 'english'
			]
		];
		
		$result = $bus->dispatch( new Congraph\Eav\Commands\Entities\EntityCreateCommand($params));

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertTrue(is_int($result->id));
		// $this->d->dump($result->toArray());

		$document = $repo->fetch($result->id, [], 'en_US');

		// $this->d->dump($result->toArray());
		// $this->d->dump($document->toArray());

		$this->assertTrue($document instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals($result->id, $document->id);

		$this->assertArraySubset($result->toArray(), $document->toArray());


		$params = [
			'entity_type' => ['id' => 1],
			'attribute_set' => ['id' => 1],
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => [
					'en_US' => 'english555'
				]
			]
		];
		
		try
		{
			$result = $bus->dispatch( new Congraph\Eav\Commands\Entities\EntityCreateCommand($params));
		}
		catch(\Exception $e)
		{
			var_dump($e->getMessage());
			$this->d->dump($e->getErrors());
		}
		

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertTrue(is_int($result->id));
		// $this->d->dump($result->toArray());

		$document = $repo->fetch($result->id, [], null);

		// $this->d->dump($result->toArray());
		// $this->d->dump($document->toArray());

		$this->assertTrue($document instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals($result->id, $document->id);

		$resultArray = $result->toArray();
		$documentArray = $result->toArray();
		unset($resultArray['created_at']);
		unset($documentArray['created_at']);
		unset($resultArray['updated_at']);
		unset($documentArray['updated_at']);
		$this->assertArraySubset($resultArray, $documentArray);
	}

	public function testUpdateEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$bus = $app->make('Illuminate\Contracts\Bus\Dispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');

		$params = [
			'fields' => [
				'attribute3' => [
					'fr_FR' => 'changed value'
				]
			]
		];
		
		$result = $bus->dispatch( new Congraph\Eav\Commands\Entities\EntityUpdateCommand($params, 1));
		
		
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertTrue(is_int($result->id));
		$this->assertEquals($result->fields->attribute3['fr_FR'], 'changed value');
		// $this->d->dump($result->toArray());
		
		$document = $repo->fetch($result->id, [], null);

		// $this->d->dump($result->toArray());
		// $this->d->dump($document->toArray());

		$this->assertTrue($document instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals($result->id, $document->id);

		$resultArray = $result->toArray();
		$documentArray = $result->toArray();
		unset($resultArray['created_at']);
		unset($documentArray['created_at']);
		unset($resultArray['updated_at']);
		unset($documentArray['updated_at']);
		$this->assertArraySubset($resultArray, $documentArray);
	}


	public function testUpdateEntityStatus()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$bus = $app->make('Illuminate\Contracts\Bus\Dispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');

		$params = [
			'status' => 'draft'
		];
		
		$result = $bus->dispatch( new Congraph\Eav\Commands\Entities\EntityUpdateCommand($params, 1));
		
		
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertTrue(is_int($result->id));
		foreach ($result->status as $key => $value)
		{
			$this->assertEquals($value, 'draft');
		}
		
		// $this->d->dump($result->toArray());
		
		$document = $repo->fetch($result->id, [], null);

		// $this->d->dump($result->toArray());
		// $this->d->dump($document->toArray());

		$this->assertTrue($document instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals($result->id, $document->id);

		$resultArray = $result->toArray();
		$documentArray = $result->toArray();
		$this->assertArraySubset($resultArray, $documentArray);

		$params = [
			'status' => 'published'
		];
		
		$result = $bus->dispatch( new Congraph\Eav\Commands\Entities\EntityUpdateCommand($params, 1));
		
		
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertTrue(is_int($result->id));
		foreach ($result->status as $key => $value)
		{
			$this->assertEquals($value, 'published');
		}
		
		// $this->d->dump($result->toArray());
		
		$document = $repo->fetch($result->id, [], null);

		// $this->d->dump($result->toArray());
		// $this->d->dump($document->toArray());

		$this->assertTrue($document instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals($result->id, $document->id);

		$resultArray = $result->toArray();
		$documentArray = $result->toArray();
		$this->assertArraySubset($resultArray, $documentArray);


		// for single locale
		// 
		// 
		$params = [
			'status' => 'draft',
			'locale' => 'en_US'
		];
		
		$result = $bus->dispatch( new Congraph\Eav\Commands\Entities\EntityUpdateCommand($params, 1));
		
		
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertTrue(is_int($result->id));
		$this->assertEquals($result->status, 'draft');
		
		// $this->d->dump($result->toArray());
		
		$document = $repo->fetch($result->id, [], 'en_US');

		// $this->d->dump($result->toArray());
		// $this->d->dump($document->toArray());

		$this->assertTrue($document instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals($result->id, $document->id);

		$resultArray = $result->toArray();
		$documentArray = $result->toArray();
		$this->assertArraySubset($resultArray, $documentArray);


		$params = [
			'status' => 'published',
			'locale' => 'en_US'
		];
		
		$result = $bus->dispatch( new Congraph\Eav\Commands\Entities\EntityUpdateCommand($params, 1));
		
		
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertTrue(is_int($result->id));
		$this->assertEquals($result->status, 'published');
		
		// $this->d->dump($result->toArray());
		
		$document = $repo->fetch($result->id, [], 'en_US');

		// $this->d->dump($result->toArray());
		// $this->d->dump($document->toArray());

		$this->assertTrue($document instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals($result->id, $document->id);

		$resultArray = $result->toArray();
		$documentArray = $result->toArray();
		$this->assertArraySubset($resultArray, $documentArray);
	}

	public function testDeleteEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$bus = $app->make('Illuminate\Contracts\Bus\Dispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');

		$result = $bus->dispatch( new Congraph\Eav\Commands\Entities\EntityDeleteCommand([], 1));

		$this->assertEquals(1, $result->id);
		// $this->d->dump($result);
		
		try
		{
			$document = $repo->fetch($result->id);
		}
		catch(\Exception $e)
		{
			$this->assertTrue($e instanceof \Congraph\Core\Exceptions\NotFoundException);
			return;
		}
		
		$this->assertFalse('There should not be any documents after delete');
	}

	public function testDeleteAttributeSet()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$app = $this->createApplication();
		$bus = $app->make('Illuminate\Contracts\Bus\Dispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');

		$result = $bus->dispatch( new Congraph\Eav\Commands\AttributeSets\AttributeSetDeleteCommand([], 1));

		$this->assertEquals(1, $result->id);
		// $this->d->dump($result);
		
		$documents = $repo->get();

		foreach ($documents as $document)
		{
			$this->assertNotEquals($result->id, $document->attribute_set_id);
		}
	}


	public function testDeleteEntityType()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$bus = $app->make('Illuminate\Contracts\Bus\Dispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');

		$result = $bus->dispatch( new Congraph\Eav\Commands\EntityTypes\EntityTypeDeleteCommand([], 1) );

		$this->assertEquals(1, $result->id);
		// $this->d->dump($result);
		
		$documents = $repo->get();

		foreach ($documents as $document)
		{
			$this->assertNotEquals($result->id, $document->entity_type_id);
		}
	}
}