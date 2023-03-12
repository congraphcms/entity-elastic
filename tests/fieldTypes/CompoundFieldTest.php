<?php

use Congraph\Core\Exceptions\ValidationException;
use Congraph\Eav\Commands\Entities\EntityCreateCommand;
use Congraph\Eav\Commands\Entities\EntityUpdateCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

require_once(__DIR__ . '/../TestCase.php');

class CompoundFieldTest extends TestCase
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
				'test_compound_text1_attribute' => 'test1',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'invalid'
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
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'test1',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'test1 test2',
			]
		], $array);

		$this->elasticSeeder->down();
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
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
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
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
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

		$this->elasticSeeder->down();
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

		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$this->elasticSeeder->up();
		$repo->refreshIndex();

		$command = $app->make(EntityCreateCommand::class);
		$command->setParams($params);
		$result = $bus->dispatch($command);
		$array = $result->toArray();
		// $this->d->dump($array);

		$repo->refreshIndex();
		$result = $repo->fetch($result->id, [], $result->locale);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
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
				'test_compound_text1_attribute' => 'changed',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'changed test2',
			]
		], $array);
		
		$this->elasticSeeder->down();
	}

	public function testUpdateLocalizedEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

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

		$app = $this->createApplication();

		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$repo->refreshIndex();
		$command = $app->make(EntityCreateCommand::class);
		$command->setParams($params);
		$result = $bus->dispatch($command);
		$array = $result->toArray();
		// $this->d->dump($array);

		$repo->refreshIndex();
		$result = $repo->fetch($result->id);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
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
				]
			]
		], $array);





		$params = [
			'fields' => [
				'test_compound_text1_attribute' => 'changed'
			]
		];
		$command = $app->make(EntityUpdateCommand::class);
		$command->setParams($params);
		$command->setId($result->id);
		$result = $bus->dispatch($command);
		$repo->refreshIndex();

		// $result = $repo->update($result->id, $params);
		$result = $repo->fetch($result->id);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($result);

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'changed',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'changed test2',
				'test_compound_localized_text_attribute' => [
					'en_US' => 'test3-en',
					'fr_FR' => 'test3-fr'
				],
				'test_localized_compound_attribute' => [
					'en_US' => 'changed test3-en',
					'fr_FR' => 'changed test3-fr'
				]
			]
		], $array);


		$params = [
			'fields' => [
				'test_compound_text1_attribute' => 'changed-again',
				'test_compound_localized_text_attribute' => [
					'en_US' => 'changed-en',
					'fr_FR' => 'changed-fr'
				],
			]
		];

		$command = $app->make(EntityUpdateCommand::class);
		$command->setParams($params);
		$command->setId($result->id);
		$result = $bus->dispatch($command);
		$repo->refreshIndex();

		// $result = $repo->update($result->id, $params);
		$result = $repo->fetch($result->id);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		// $this->d->dump($result);

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'changed-again',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'changed-again test2',
				'test_compound_localized_text_attribute' => [
					'en_US' => 'changed-en',
					'fr_FR' => 'changed-fr'
				],
				'test_localized_compound_attribute' => [
					'en_US' => 'changed-again changed-en',
					'fr_FR' => 'changed-again changed-fr'
				]
			]
		], $array);

		$params = [
			'locale' => 'en_US',
			'fields' => [
				// 'test_compound_text1_attribute' => 'back',
				'test_compound_localized_text_attribute' => 'to-en'
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
		// $this->d->dump($result);

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'changed-again',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'changed-again test2',
				'test_compound_localized_text_attribute' => 'to-en',
				'test_localized_compound_attribute' => 'changed-again to-en'
			]
		], $array);

		$result = $repo->fetch($result->id);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'changed-again',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'changed-again test2',
				'test_compound_localized_text_attribute' => [
					'en_US' => 'to-en',
					'fr_FR' => 'changed-fr'
				],
				'test_localized_compound_attribute' => [
					'en_US' => 'changed-again to-en',
					'fr_FR' => 'changed-again changed-fr'
				]
			]
		], $array);


		$params = [
			'locale' => 'en_US',
			'fields' => [
				'test_compound_text1_attribute' => 'back'
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
		// $this->d->dump($result);

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'back',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'back test2',
				'test_compound_localized_text_attribute' => 'to-en',
				'test_localized_compound_attribute' => 'back to-en'
			]
		], $array);

		$result = $repo->fetch($result->id);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();

		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'fields' => [
				'test_compound_text1_attribute' => 'back',
				'test_compound_text2_attribute' => 'test2',
				'test_compound_attribute' => 'back test2',
				'test_compound_localized_text_attribute' => [
					'en_US' => 'to-en',
					'fr_FR' => 'changed-fr'
				],
				'test_localized_compound_attribute' => [
					'en_US' => 'back to-en',
					'fr_FR' => 'back changed-fr'
				]
			]
		], $array);

		$this->elasticSeeder->down();
		
	}
}