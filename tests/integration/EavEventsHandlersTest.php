<?php

use Congraph\Core\Exceptions\ValidationException;
use Congraph\Eav\Commands\Entities\EntityCreateCommand;
use Congraph\Eav\Commands\Entities\EntityUpdateCommand;
use Congraph\Eav\Commands\Entities\EntityDeleteCommand;
use Congraph\EntityElastic\Commands\Entities\EntityFetchCommand;
use Congraph\EntityElastic\Commands\Entities\EntityGetCommand;
use Congraph\Filesystem\Commands\Files\FileDeleteCommand;
use Congraph\Eav\Commands\AttributeSets\AttributeSetDeleteCommand;
use Congraph\Eav\Commands\EntityTypes\EntityTypeDeleteCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Elasticsearch\ClientBuilder;

require_once(__DIR__ . '/../TestCase.php');

class EavEventsHandlersTest extends TestCase
{

	public function testCreateEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');
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
		
		$command = $app->make(EntityCreateCommand::class);
		$command->setParams($params);
		$result = $bus->dispatch($command);

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
			$command = $app->make(EntityCreateCommand::class);
		$command->setParams($params);
		$result = $bus->dispatch($command);
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
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');

		$params = [
			'fields' => [
				'attribute3' => [
					'fr_FR' => 'changed value'
				]
			]
		];
		
		$command = $app->make(EntityUpdateCommand::class);
		$command->setParams($params);
		$command->setId(1);
		$result = $bus->dispatch($command);
		
		
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
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');

		$params = [
			'status' => 'draft'
		];
		
		$command = $app->make(EntityUpdateCommand::class);
		$command->setParams($params);
		$command->setId(1);
		$result = $bus->dispatch($command);
		
		
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
		
		$command = $app->make(EntityUpdateCommand::class);
		$command->setParams($params);
		$command->setId(1);
		$result = $bus->dispatch($command);
		
		
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
		
		$command = $app->make(EntityUpdateCommand::class);
		$command->setParams($params);
		$command->setId(1);
		$result = $bus->dispatch($command);
		
		
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
		
		$command = $app->make(EntityUpdateCommand::class);
		$command->setParams($params);
		$command->setId(1);
		$result = $bus->dispatch($command);
		
		
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
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$command = $app->make(EntityDeleteCommand::class);
		$command->setId(1);
		$result = $bus->dispatch($command);

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
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');

		$command = $app->make(AttributeSetDeleteCommand::class);
		$command->setId(1);
		$result = $bus->dispatch($command);

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
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');

		$command = $app->make(EntityTypeDeleteCommand::class);
		$command->setId(1);
		$result = $bus->dispatch($command);

		$this->assertEquals(1, $result->id);
		// $this->d->dump($result);
		
		$documents = $repo->get();

		foreach ($documents as $document)
		{
			$this->assertNotEquals($result->id, $document->entity_type_id);
		}
	}
}