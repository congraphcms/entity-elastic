<?php

use Congraph\Core\Exceptions\ValidationException;
use Congraph\Eav\Commands\Entities\EntityCreateCommand;
use Congraph\Eav\Commands\Entities\EntityUpdateCommand;
use Congraph\Eav\Commands\Entities\EntityDeleteCommand;
use Congraph\EntityElastic\Commands\Entities\EntityFetchCommand;
use Congraph\EntityElastic\Commands\Entities\EntityGetCommand;
use Congraph\Filesystem\Commands\Files\FileDeleteCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Elasticsearch\ClientBuilder;

require_once(__DIR__ . '/../TestCase.php');

class DeliveryCommandsTest extends TestCase
{
	
	public function testFetchEntity()
	{

		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();
		$repo->refreshIndex();
		$command = $app->make(EntityFetchCommand::class);
		$command->setParams(['locale' => 'en_US']);
		$command->setId(1);
		$result = $bus->dispatch($command);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertTrue(is_int($result->id));


		// $this->d->dump($result->toArray());
		

	}

	
	public function testGetEntities()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();
		$repo->refreshIndex();

		$command = $app->make(EntityGetCommand::class);
		$command->setParams([]);
		$result = $bus->dispatch($command);
		// $this->d->dump($result->toArray());
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		$this->assertTrue(count($result) > 0);
		

	}

	public function testGetParams()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();
		$repo->refreshIndex();

		// $mapping = $repo->getMappings('congraph_entities');
		// $this->d->dump($mapping);
		$command = $app->make(EntityGetCommand::class);
		$command->setParams(['locale' => 'en_US', 'sort' => ['fields.attribute3'], 'limit' => 3, 'offset' => 0]);
		$result = $bus->dispatch($command);

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		$this->assertEquals(3, count($result));

		// $this->d->dump($result->toArray());
	}

	public function testGetFilters()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();
		$repo->refreshIndex();

		$filter = [ 'fields.attribute3' => 'value3-en' ];

		$command = $app->make(EntityGetCommand::class);
		$command->setParams(['filter' => $filter, 'locale' => 'en_US', 'sort' => ['fields.attribute1']]);
		$result = $bus->dispatch($command);
		// $this->d->dump($result->toArray());
		
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		$this->assertEquals(4, count($result));

		

	}

}