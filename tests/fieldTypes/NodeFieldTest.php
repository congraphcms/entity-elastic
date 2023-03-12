<?php

use Congraph\Core\Exceptions\ValidationException;
use Congraph\Eav\Commands\Entities\EntityCreateCommand;
use Congraph\Eav\Commands\Entities\EntityUpdateCommand;
use Congraph\Eav\Commands\Entities\EntityDeleteCommand;
use Congraph\Filesystem\Commands\Files\FileDeleteCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Elasticsearch\ClientBuilder;

require_once(__DIR__ . '/../TestCase.php');

class NodeFieldTest extends TestCase
{

	public function testCreateEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'locale' => 'en_US',
			'fields' => [
				'test_node_attribute' => ['id' => 1, 'type' => 'entity']
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertArraySubset([
			'type' => 'entity',
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_node_attribute' => [
					'id' => 1, 
					'type' => 'entity',
					'fields' => [
        				'attribute1' => 'value1'
        			]
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
				'test_node_attribute' => ['id' => 1, 'type' => 'entity']
			]
		];

		$app = $this->createApplication();

		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$this->elasticSeeder->up();
		$repo->refreshIndex();
		$command = $app->make(EntityCreateCommand::class);
		$command->setParams($params);
		$result = $bus->dispatch($command);
		$array = $result->toArray();
		// $this->d->dump($array);

		$result = $repo->fetch($result->id, [], $result->locale);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);
		

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_node_attribute' => [
					'id' => 1, 
					'type' => 'entity',
					'fields' => [
        				'attribute1' => 'value1'
        			]
				],
			]
		], $array);



		$params = [
			'locale' => 'en_US',
			'fields' => [
				'test_node_attribute' => ['id' => 2, 'type' => 'entity']
			]
		];

		$command = $app->make(EntityUpdateCommand::class);
		$command->setParams($params);
		$command->setId($result->id);
		$result = $bus->dispatch($command);
		$repo->refreshIndex();

		// $result = $repo->update($result->id, $params);
		$result = $repo->fetch($result->id, [], $result->locale);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($result->toArray());

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_node_attribute' => [
					'id' => 2, 
					'type' => 'entity',
					'fields' => [
        				'attribute1' => 'value12'
        			]
				],
			]
		], $array);
		
	}
}