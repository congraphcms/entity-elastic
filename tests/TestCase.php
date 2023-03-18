<?php

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Congraph\Core\Exceptions\ValidationException;
use Symfony\Component\VarDumper\VarDumper as Dumper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

require_once(__DIR__ . '/database/seeders/EavDbSeeder.php');
require_once(__DIR__ . '/database/seeders/LocaleDbSeeder.php');
require_once(__DIR__ . '/database/seeders/FileDbSeeder.php');
require_once(__DIR__ . '/database/seeders/WorkflowDbSeeder.php');
require_once(__DIR__ . '/database/seeders/ClearDB.php');
require_once(__DIR__ . '/database/seeders/ElasticIndexSeeder.php');


abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use ArraySubsetAsserts;
    // ----------------------------------------
    // ENVIRONMENT
    // ----------------------------------------

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

    protected function getEnvironmentSetUp($app)
    {
        
        parent::getEnvironmentSetUp($app);
    }

	/**
     * Resolve application core configuration implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function resolveApplicationConfiguration($app)
    {
		// make sure, our .env file is loaded
        $app->useEnvironmentPath(__DIR__.'/..');
		$app->useStoragePath(realpath(__DIR__.'./storage/'));
        $app->bootstrapWith([LoadEnvironmentVariables::class]);
        parent::resolveApplicationConfiguration($app);
    }

    /**
	 * Define environment setup.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 *
	 * @return void
	 */
	protected function defineEnvironment($app)
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

    	// ----------------------------------------
    // DATABASE
    // ----------------------------------------

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {

		/**
		 * Elastic Migrations
		 */
        $this->loadMigrationsFrom(realpath(__DIR__.'/../../database/migrations'));

        $this->artisan('migrate', ['--database' => 'testbench'])->run();

		/**
		 * EAV Migrations
		 */
		$this->loadMigrationsFrom(realpath(__DIR__.'/../../vendor/Congraph/EAV/database/migrations'));

        $this->artisan('migrate', ['--database' => 'testbench'])->run();


		/**
		 * FileSystem Migrations
		 */
		$this->loadMigrationsFrom(realpath(__DIR__.'/../../vendor/Congraph/Filesystem/database/migrations'));

        $this->artisan('migrate', ['--database' => 'testbench'])->run();


		/**
		 * Locales Migrations
		 */
		$this->loadMigrationsFrom(realpath(__DIR__.'/../../vendor/Congraph/Locales/database/migrations'));

        $this->artisan('migrate', ['--database' => 'testbench'])->run();


		/**
		 * Workflows Migrations
		 */
		$this->loadMigrationsFrom(realpath(__DIR__.'/../../vendor/Congraph/Workflows/database/migrations'));

        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->beforeApplicationDestroyed(function () {
			/**
			 * Elastic Migrations
			 */
			$this->loadMigrationsFrom(realpath(__DIR__.'/../../database/migrations'));
            $this->artisan('migrate:reset', ['--database' => 'testbench'])->run();

			/**
			 * EAV Migrations
			 */
			$this->loadMigrationsFrom(realpath(__DIR__.'/../../vendor/Congraph/EAV/database/migrations'));
            $this->artisan('migrate:reset', ['--database' => 'testbench'])->run();

			/**
			 * FileSystem Migrations
			 */
			$this->loadMigrationsFrom(realpath(__DIR__.'/../../vendor/Congraph/Filesystem/database/migrations'));
            $this->artisan('migrate:reset', ['--database' => 'testbench'])->run();

			/**
			 * Locales Migrations
			 */
			$this->loadMigrationsFrom(realpath(__DIR__.'/../../vendor/Congraph/Locales/database/migrations'));
            $this->artisan('migrate:reset', ['--database' => 'testbench'])->run();

			/**
			 * Workflows Migrations
			 */
			$this->loadMigrationsFrom(realpath(__DIR__.'/../../vendor/Congraph/Workflows/database/migrations'));
            $this->artisan('migrate:reset', ['--database' => 'testbench'])->run();
        });
    }

	// ----------------------------------------
    // SETUP
    // ----------------------------------------

	public function setUp(): void
	{
		parent::setUp();

		
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

        $client = $this->app->make('Elasticsearch\Client');
		$this->elasticSeeder = 
            new Database\Seeders\ElasticIndexSeeder($client);
	}

	public function tearDown(): void
	{
		$this->artisan('db:seed', [
			'--class' => 'ClearDB'
		]);
		DB::disconnect();

		parent::tearDown();
	}
}