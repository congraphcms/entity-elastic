<?php

require_once __DIR__ . '/database/seeders/ClearDB.php';
require_once __DIR__ . '/database/seeders/EavDbSeeder.php';
require_once __DIR__ . '/database/seeders/ElasticIndexSeeder.php';
require_once __DIR__ . '/database/seeders/FileDbSeeder.php';
require_once __DIR__ . '/database/seeders/helpers.php';
require_once __DIR__ . '/database/seeders/LocaleDbSeeder.php';
require_once __DIR__ . '/database/seeders/WorkflowDbSeeder.php';

use Congraph\Core\Bus\CommandDispatcher;
use Congraph\Core\CoreServiceProvider;
use Congraph\Eav\EavServiceProvider;
use Congraph\Eav\Services\MetaDataService;
use Congraph\EntityElastic\EntityElasticServiceProvider;
use Congraph\Filesystem\FilesystemServiceProvider;
use Congraph\Locales\LocalesServiceProvider;
use Congraph\Workflows\WorkflowsServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OpenSearch\Client;
use PHPUnit\Framework\Assert;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
	protected Database\Seeders\ElasticIndexSeeder $elasticSeeder;

	protected function getPackageProviders($app)
	{
		return [
			CoreServiceProvider::class,
			LocalesServiceProvider::class,
			FilesystemServiceProvider::class,
			WorkflowsServiceProvider::class,
			EavServiceProvider::class,
			EntityElasticServiceProvider::class,
		];
	}

	protected function defineEnvironment($app)
	{
		$driver = env('DB_CONNECTION', 'sqlite');

		$connection = [
			'driver' => $driver,
			'prefix' => '',
		];

		if ($driver === 'sqlite') {
			$connection['database'] = env('DB_DATABASE', ':memory:');
			$connection['foreign_key_constraints'] = true;
		} else {
			$connection['host'] = env('DB_HOST', '127.0.0.1');
			$connection['port'] = env('DB_PORT', $driver === 'pgsql' ? '5432' : '3306');
			$connection['database'] = env('DB_DATABASE', 'congraph_test');
			$connection['username'] = env('DB_USERNAME', 'congraph');
			$connection['password'] = env('DB_PASSWORD', 'congraph');
			$connection['charset'] = 'utf8';
			$connection['prefix_indexes'] = true;
			$connection['schema'] = env('DB_SCHEMA', 'public');
			$connection['sslmode'] = env('DB_SSLMODE', 'prefer');

			if ($driver === 'mysql') {
				$connection['charset'] = 'utf8mb4';
				$connection['collation'] = 'utf8mb4_unicode_ci';
			}
		}

		$app['config']->set('database.default', 'testbench');
		$app['config']->set('database.connections.testbench', $connection);
		$app['config']->set('cache.default', 'file');
		$app['config']->set('cache.stores.file', [
			'driver' => 'file',
			'path' => realpath(__DIR__ . '/storage/cache/') ?: __DIR__ . '/storage/cache',
		]);
		$app['config']->set('filesystems.default', 'local');
		$app['config']->set('filesystems.disks.local', [
			'driver' => 'local',
			'root' => realpath(__DIR__ . '/storage/') ?: __DIR__ . '/storage',
		]);
		$app['config']->set('cb.eav.using_elastic', true);
		$app['config']->set('cb.elastic.connections.local.hosts', explode(',', env('OPENSEARCH_HOSTS', env('ES_HOSTS', 'localhost:9200'))));
	}

	protected function defineDatabaseMigrations()
	{
		$this->loadMigrationsFrom(realpath(__DIR__ . '/../database/migrations'));
		$this->loadMigrationsFrom(realpath(__DIR__ . '/../../eav/database/migrations'));
		$this->loadMigrationsFrom(realpath(__DIR__ . '/../../filesystem/database/migrations'));
		$this->loadMigrationsFrom(realpath(__DIR__ . '/../../locales/database/migrations'));
		$this->loadMigrationsFrom(realpath(__DIR__ . '/../../workflows/database/migrations'));

		$this->artisan('migrate', ['--database' => 'testbench'])->run();
	}

	protected function setUp(): void
	{
		parent::setUp();

		foreach ($this->seeders() as $seeder) {
			$this->artisan('db:seed', ['--class' => $seeder]);
		}

		$this->resetMetaDataCache();

		Storage::deleteDirectory('files');
		Storage::deleteDirectory('uploads');
		Storage::makeDirectory('files');
		Storage::makeDirectory('uploads');
		Storage::copy('temp/test.jpg', 'files/test.jpg');
		Storage::copy('temp/test.jpg', 'files/test2.jpg');
		Storage::copy('temp/test.jpg', 'uploads/test.jpg');

		$this->elasticSeeder = new Database\Seeders\ElasticIndexSeeder($this->app->make(Client::class));
	}

	protected function tearDown(): void
	{
		foreach ($this->cleanupSeeders() as $seeder) {
			$this->artisan('db:seed', ['--class' => $seeder]);
		}

		parent::tearDown();
	}

	protected function seeders(): array
	{
		return [
			Database\Seeders\EavDbSeeder::class,
			Database\Seeders\LocaleDbSeeder::class,
			Database\Seeders\FileDbSeeder::class,
			Database\Seeders\WorkflowDbSeeder::class,
		];
	}

	protected function cleanupSeeders(): array
	{
		return [
			Database\Seeders\ClearDB::class,
		];
	}

	protected function dispatch(object $command): mixed
	{
		return $this->app->make(CommandDispatcher::class)->dispatch($command);
	}

	protected function resetMetaDataCache(): void
	{
		foreach (['attributes', 'attributeSets', 'entityTypes', 'workflows', 'workflowPoints', 'locales'] as $key) {
			Cache::forget($key);
		}

		$reflection = new ReflectionClass(MetaDataService::class);

		foreach (['attributes', 'attributeSets', 'entityTypes', 'workflows', 'workflowPoints', 'locales'] as $property) {
			$refProperty = $reflection->getProperty($property);
			$refProperty->setAccessible(true);
			$refProperty->setValue(null, null);
		}

		$this->app->forgetInstance(MetaDataService::class);
		$this->app->forgetInstance('Congraph\Eav\Services\MetaDataService');
	}

	protected function assertArraySubset(array $subset, array $array, string $path = 'root'): void
	{
		foreach ($subset as $key => $expected) {
			Assert::assertArrayHasKey($key, $array, "Missing key [$path.$key]");

			$actual = $array[$key];

			if (is_array($expected)) {
				Assert::assertIsArray($actual, "Expected array at [$path.$key]");
				$this->assertArraySubset($expected, $actual, "$path.$key");
				continue;
			}

			Assert::assertSame($expected, $actual, "Mismatch at [$path.$key]");
		}
	}
}
