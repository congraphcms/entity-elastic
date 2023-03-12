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

class FieldsTest extends TestCase
{

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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_boolean_attribute' => false
			]
		], $array);
	}

	public function testDatetimeField()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$date =  \Carbon\Carbon::now()->format('c');

		// create field
		$params = [
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_datetime_attribute' => $date
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_datetime_attribute' => $date
			]
		], $array);

		$newDate =  \Carbon\Carbon::now()->addDays(1)->format('c');

		// update field
		$params = [
			'fields' => [
				'test_datetime_attribute' => $newDate
			]
		];
		$result = $repo->update($result->id, $params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			'fields' => [
				'test_datetime_attribute' => $newDate
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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

		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$command = $app->make(FileDeleteCommand::class);
		$command->setId(1);
		$result = $bus->dispatch($command);
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithFile->id);
		$this->assertFalse(isset($changedEntity->fields->test_asset_attribute));

		$command = $app->make(FileDeleteCommand::class);
		$command->setId(2);
		$result = $bus->dispatch($command);
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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

		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$command = $app->make(FileDeleteCommand::class);
		$command->setId(1);
		$result = $bus->dispatch($command);
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithFile->id);
		$array = $changedEntity->toArray();
		// $this->d->dump($array);
		$this->assertTrue(count($array['fields']['test_assetcollection_attribute']) > 0);
		foreach ($array['fields']['test_assetcollection_attribute'] as $value)
		{
			$this->assertNotEquals(1, $value['id']);
		}

		$command = $app->make(FileDeleteCommand::class);
		$command->setId(2);
		$result = $bus->dispatch($command);
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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

		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$command = $app->make(EntityDeleteCommand::class);
		$command->setId(1);
		$result = $bus->dispatch($command);
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithRelation->id);
		$this->assertFalse(isset($changedEntity->fields->test_relation_attribute));

		$command = $app->make(EntityDeleteCommand::class);
		$command->setId(2);
		$result = $bus->dispatch($command);
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($array);

		// $this->assertTrue(is_int($result->id));
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

		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$command = $app->make(EntityDeleteCommand::class);
		$command->setId(1);
		$result = $bus->dispatch($command);
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithRelation->id);
		$array = $changedEntity->toArray();
		// $this->d->dump($array);
		$this->assertTrue(count($array['fields']['test_relationcollection_attribute']) > 0);
		foreach ($array['fields']['test_relationcollection_attribute'] as $value)
		{
			$this->assertNotEquals(1, $value['id']);
		}

		$command = $app->make(EntityDeleteCommand::class);
		$command->setId(2);
		$result = $bus->dispatch($command);
		$repo->refreshIndex();

		$changedEntity = $repo->fetch($entityWithRelation->id);
		$array = $changedEntity->toArray();

		$this->assertTrue(count($array['fields']['test_relationcollection_attribute']) == 0);
	}
}