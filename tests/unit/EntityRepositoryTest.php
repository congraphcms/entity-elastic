<?php

use Congraph\Core\Exceptions\ValidationException;
use Congraph\Eav\Commands\EntityTypes\EntityTypeFetchCommand;
use Congraph\Eav\Commands\AttributeSets\AttributeSetFetchCommand;
use Congraph\Eav\Commands\Attributes\AttributeFetchCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Elasticsearch\ClientBuilder;

require_once(__DIR__ . '/../TestCase.php');

class EntityRepositoryTest extends TestCase
{

	public function testConfig()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$hosts = Config::get('cb.elastic.hosts');

		$this->assertTrue(is_array($hosts));
		$this->assertTrue(isset($hosts[0]));

		// $this->d->dump($hosts);
	}


	public function testConstructor()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		

		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->assertTrue($repo instanceof Congraph\EntityElastic\Repositories\EntityRepository);

		$this->assertTrue($repo->indexExists(Config::get('cb.elastic.index_prefix') . 'entities'));
	}

	public function testFetch() {
		fwrite(STDOUT, __METHOD__ . "\n");
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->fetch(1);

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals('1', $result->id);
		$this->assertArrayHasKey('entity_type_id', $result->toArray());
		$this->assertArrayHasKey('attribute_set_id', $result->toArray());
		$this->assertArrayHasKey('entity_type', $result->toArray());
		$this->assertArrayHasKey('entity_endpoint', $result->toArray());
		$this->assertArrayHasKey('workflow_id', $result->toArray());
		$this->assertArrayHasKey('attribute_set_code', $result->toArray());
		$this->assertArrayHasKey('created_at', $result->toArray());
		$this->assertArrayHasKey('updated_at', $result->toArray());

		$this->assertArraySubset([
			"id" => "1",
			"type" => "entity",
			"status" => [
				"en_US" => "published",
				"fr_FR" => "published"
			],
			"fields" => [
				"attribute2" => "value2",
				"attribute1" => "value1",
				"attribute3" => [
				  "en_US" => "value3-en",
				  "fr_FR" => "value3-fr"
				]
			]

		], $result->toArray());

		// $this->d->dump($result->toArray());

		$result = $repo->fetch(1, [], 'en_US');

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals('1', $result->id);
		$this->assertArrayHasKey('entity_type_id', $result->toArray());
		$this->assertArrayHasKey('attribute_set_id', $result->toArray());
		$this->assertArrayHasKey('entity_type', $result->toArray());
		$this->assertArrayHasKey('entity_endpoint', $result->toArray());
		$this->assertArrayHasKey('workflow_id', $result->toArray());
		$this->assertArrayHasKey('attribute_set_code', $result->toArray());
		$this->assertArrayHasKey('created_at', $result->toArray());
		$this->assertArrayHasKey('updated_at', $result->toArray());

		$this->assertArraySubset([
			"id" => "1",
			"type" => "entity",
			"status" => "published",
			"fields" => [
				"attribute2" => "value2",
				"attribute1" => "value1",
				"attribute3" => "value3-en"
			]

		], $result->toArray());
		// $this->d->dump($result->toArray());

		$result = $repo->fetch(2, [], null, 'public');

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals('2', $result->id);
		$this->assertArrayHasKey('entity_type_id', $result->toArray());
		$this->assertArrayHasKey('attribute_set_id', $result->toArray());
		$this->assertArrayHasKey('entity_type', $result->toArray());
		$this->assertArrayHasKey('entity_endpoint', $result->toArray());
		$this->assertArrayHasKey('workflow_id', $result->toArray());
		$this->assertArrayHasKey('attribute_set_code', $result->toArray());
		$this->assertArrayHasKey('created_at', $result->toArray());
		$this->assertArrayHasKey('updated_at', $result->toArray());

		$this->assertArraySubset([
			"id" => "2",
			"type" => "entity",
			"status" => "public",
			"fields" => [
				"attribute2" => "value22",
				"attribute1" => "value12"
			]

		], $result->toArray());
		// $this->d->dump($result->toArray());

		$result = $repo->fetch(3, [], null, 'published');
		// $this->d->dump($result->toArray());

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals('3', $result->id);
		$this->assertArrayHasKey('entity_type_id', $result->toArray());
		$this->assertArrayHasKey('attribute_set_id', $result->toArray());
		$this->assertArrayHasKey('entity_type', $result->toArray());
		$this->assertArrayHasKey('entity_endpoint', $result->toArray());
		$this->assertArrayHasKey('workflow_id', $result->toArray());
		$this->assertArrayHasKey('attribute_set_code', $result->toArray());
		$this->assertArrayHasKey('created_at', $result->toArray());
		$this->assertArrayHasKey('updated_at', $result->toArray());

		$this->assertArraySubset([
			"id" => "3",
			"type" => "entity",
			"status" => [
				"en_US" => "published"
			],
			"fields" => [
				"attribute2" => "value22",
				"attribute1" => "value12",
				"attribute3" => [
					"en_US" => "value3-en"
				]
			]

		], $result->toArray());

		$this->assertArrayNotHasKey('fr_FR', $result->toArray()['fields']['attribute3']);
		
	}

	public function testFetchFailOnUnknownID()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$this->expectException(\Congraph\Core\Exceptions\NotFoundException::class);
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->fetch(133);
	}

	public function testFetchFailOnLocale()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$this->expectException(\Congraph\Core\Exceptions\NotFoundException::class);
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->fetch(1, [], null, 'sr_RS');

		// $this->d->dump($result->toArray());
	}

	public function testFetchFailOnStatus()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$this->expectException(\Congraph\Core\Exceptions\NotFoundException::class);
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->fetch(2, [], null, 'trashed');

		// $this->d->dump($result->toArray());
	}


	public function testGet()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$repo->refreshIndex();

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	null, 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);

		// $this->d->dump($result->toArray());
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		$this->assertTrue(!empty($result->toArray()));
	}

	public function testGetSort()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$repo->refreshIndex();

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'fields.attribute1', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		$this->assertTrue($array[0]['fields']['attribute1'] < $array[count($array) - 1]['fields']['attribute1']);

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'-entity_type_id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);

		$array = $result->toArray();

		// $this->d->dump($array);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		$this->assertTrue($array[0]['entity_type_id'] > $array[count($array) - 1]['entity_type_id']);
	}

	public function testGetPaging()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$repo->refreshIndex();

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	1, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		$this->assertEquals(2, $array[0]['id']);

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	0, 
			/*$limit = */  	2, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		$this->assertEquals(2, count($array));

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	1, 
			/*$limit = */  	2, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		$this->assertEquals(2, $array[0]['id']);
		$this->assertEquals(2, count($array));
	}

	public function testGetLocaleFilter()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();
		$repo->refreshIndex();

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	'en_US', //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		$this->assertEquals('en_US', $array[0]['locale']);
		$this->assertTrue(is_string($array[0]['status']));
		$this->assertEquals('value3-en', $array[0]['fields']['attribute3']);
	}

	public function testGetStatusFilter()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$repo->refreshIndex();

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	'published' //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		foreach ($array as $entity)
		{
			if(is_string($entity['status']))
			{
				$this->assertEquals('published', $entity['status']);
				continue;
			}

			foreach ($entity['status'] as $locale => $status)
			{
				$this->assertEquals('published', $status);
			}
		}

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			$include = 	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	'public' //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		foreach ($array as $entity)
		{
			if(is_string($entity['status']))
			{
				$this->assertEquals('public', $entity['status']);
				continue;
			}

			foreach ($entity['status'] as $locale => $status)
			{
				$this->assertEquals('public', $status);
			}
		}

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	'en_US', //"fr_FR", 
			/*$status = */	'published' //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Collection);
		foreach ($array as $entity)
		{
			if(is_string($entity['status']))
			{
				$this->assertEquals('published', $entity['status']);
				continue;
			}

			foreach ($entity['status'] as $locale => $status)
			{
				$this->assertEquals('published', $status);
			}
		}
	}

	public function testGetFiltering()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$repo->refreshIndex();

		$result = $repo->get(
			/*$filter = */ 	['entity_type_id' => 1], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertEquals(1, $entity['entity_type_id']);
		}

		$result = $repo->get(
			/*$filter = */ 	['entity_type_id' => ['ne' => 1]], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertNotEquals(1, $entity['entity_type_id']);
		}

		$result = $repo->get(
			/*$filter = */ 	['id' => ['gt' => 1]], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue($entity['id'] > 1);
		}

		$result = $repo->get(
			/*$filter = */ 	['id' => ['gte' => 2]], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue($entity['id'] >= 2);
		}

		$result = $repo->get(
			/*$filter = */ 	['id' => ['lt' => 3]], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue($entity['id'] < 3);
		}

		$result = $repo->get(
			/*$filter = */ 	['id' => ['lte' => 2]], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue($entity['id'] <= 2);
		}

		$result = $repo->get(
			/*$filter = */ 	['id' => ['in' => [1,2]]], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(in_array($entity['id'], [1,2]));
		}

		$result = $repo->get(
			/*$filter = */ 	['id' => ['in' => '1,2']], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(in_array($entity['id'], [1,2]));
		}

		$result = $repo->get(
			/*$filter = */ 	['id' => ['in' => '1']], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(in_array($entity['id'], [1,2]));
		}

		$result = $repo->get(
			/*$filter = */ 	['id' => ['nin' => [1,2]]], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(!in_array($entity['id'], [1,2]));
		}

		$result = $repo->get(
			/*$filter = */ 	['id' => ['nin' => '1,2']], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(!in_array($entity['id'], [1,2]));
		}
	}

	public function testGetFieldFiltering()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$repo->refreshIndex();

		$result = $repo->get(
			/*$filter = */ 	['fields.attribute1' => 'value1'], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertEquals('value1', $entity['fields']['attribute1']);
		}

		$result = $repo->get(
			/*$filter = */ 	['fields.attribute1' => ['e' => 'value1']], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertEquals('value1', $entity['fields']['attribute1']);
		}

		$result = $repo->get(
			/*$filter = */ 	['fields.attribute1' => ['ne' => 'value1']], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertNotEquals('value1', $entity['fields']['attribute1']);
		}

		$result = $repo->get(
			/*$filter = */ 	['fields.attribute1' => ['in' => 'value1']], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(in_array($entity['fields']['attribute1'], ['value1']));
		}

		$result = $repo->get(
			/*$filter = */ 	['fields.attribute1' => ['in' => 'value1,value12']], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(in_array($entity['fields']['attribute1'], ['value1', 'value12']));
		}

		$result = $repo->get(
			/*$filter = */ 	['fields.attribute1' => ['in' => ['value1','value12']]], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(in_array($entity['fields']['attribute1'], ['value1', 'value12']));
		}

		$result = $repo->get(
			/*$filter = */ 	['fields.attribute1' => ['nin' => 'value1']], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(!in_array($entity['fields']['attribute1'], ['value1']));
		}

		$result = $repo->get(
			/*$filter = */ 	['fields.attribute1' => ['nin' => 'value1,value12']], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(!in_array($entity['fields']['attribute1'], ['value1', 'value12']));
		}

		$result = $repo->get(
			/*$filter = */ 	['fields.attribute1' => ['nin' => ['value1','value12']]], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	'id', 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		// $this->d->dump($array);

		foreach ($array as $entity)
		{
			$this->assertTrue(!in_array($entity['fields']['attribute1'], ['value1', 'value12']));
		}
	}

	public function testCreateEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$params = [
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'locale' => 'en_US',
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => 'english'
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
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'status' => 'draft',
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => 'english'
			]
		], $array);

		$result = $repo->fetch($result->id);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'status' => [
				'en_US' => 'draft'
			],
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => [
					'en_US' => 'english'
				]
			]
		], $array);
	}

	public function testCreateEntityWithMultipleLocales()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$params = [
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => [
					'en_US' => 'english',
					'fr_FR' => 'french'
				]
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$array = $result->toArray();
		// $this->d->dump($array);

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		// $this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'status' => [
				'en_US' => 'draft',
				'fr_FR' => 'draft'
			],
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => [
					'en_US' => 'english',
					'fr_FR' => 'french'
				]
			]
		], $array);
		
	}

	public function testCreateEntityWithStatus()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$params = [
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'status' => 'draft',
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => [
					'en_US' => 'english',
					'fr_FR' => 'french'
				]
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$array = $result->toArray();

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		// $this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'status' => [
				'en_US' => 'draft',
				'fr_FR' => 'draft'
			],
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => [
					'en_US' => 'english',
					'fr_FR' => 'french'
				]
			]
		], $array);
		// $this->d->dump($result->toArray());
	}

	public function testCreateEntityWithStatusForEachLocale()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$params = [
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'status' => [
				'en_US' => 'draft',
				'fr_FR' => 'published'
			],
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => [
					'en_US' => 'english',
					'fr_FR' => 'french'
				]
			]
		];

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->create($params);
		$array = $result->toArray();

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		// $this->assertTrue(is_int($result->id));
		$this->assertArraySubset([
			"type" => "entity",
			'entity_type_id' => 1,
			'attribute_set_id' => 1,
			'status' => [
				'en_US' => 'draft',
				'fr_FR' => 'published'
			],
			'fields' => [
				'attribute1' => '234',
				'attribute2' => '',
				'attribute3' => [
					'en_US' => 'english',
					'fr_FR' => 'french'
				]
			]
		], $array);
		// $this->d->dump($result->toArray());
	}

	public function testUpdateEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$params = [
			'fields' => [
				'attribute3' => [
					'fr_FR' => 'changed value'
				]
			]
		];

		$result = $repo->update(1, $params);

		// $this->d->dump($result->toArray());
		
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertTrue(is_int($result->id));
		$this->assertEquals('changed value', $result->fields->attribute3->fr_FR);
	}

	public function testUpdateStatus()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$params = [
			'locale' => 'en_US',
			'status' => 'trashed'
		];
		
		$result = $repo->update(1, $params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		
		$this->assertEquals('trashed', $result->status);
		
	}

	public function testUpdateStatusForAllLocales()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$params = [
			'status' => 'trashed'
		];
		
		$result = $repo->update(1, $params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		
		$this->assertTrue(is_array($result->status));

		foreach ($result->status as $locale => $status)
		{
			$this->assertEquals('trashed', $status);
		}
	}

	public function testUpdateDifferentStatusForLocales()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$params = [
			'status' => [
				'en_US' => 'trashed',
				'fr_FR' => 'draft'
			]
		];
		
		$result = $repo->update(1, $params);
		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$array = $result->toArray();
		
		$this->assertTrue(is_array($result->status));

		$this->assertArraySubset([
			'en_US' => 'trashed',
			'fr_FR' => 'draft'
		], $result->status);
	}

	public function testDeleteEntity()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$result = $repo->delete(1);

		$this->assertTrue($result instanceof Congraph\Core\Repositories\Model);
		$this->assertEquals(1, $result->id);
		

	}

	public function testDeleteByAttribute()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();
		$repo->refreshIndex();
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$command = $app->make(AttributeFetchCommand::class);
		$command->setId(1);
		$attribute = $bus->dispatch($command);

		$result = $repo->deleteByAttribute($attribute);
		$repo->refreshIndex();
		$result = $repo->get();
		$array = $result->toArray();
		// $this->d->dump($array);
		foreach ($array as $entity)
		{
			$this->assertTrue($entity['fields'][$attribute->code] === null);
		}
	}

	public function testDeleteByLocalizedAttribute()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();

		$repo->refreshIndex();

		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$command = $app->make(AttributeFetchCommand::class);
		$command->setId(3);
		$attribute = $bus->dispatch($command);

		$result = $repo->deleteByAttribute($attribute);

		$repo->refreshIndex();

		$result = $repo->get(
			/*$filter = */ 	[], 
			/*$offset = */ 	null, 
			/*$limit = */  	null, 
			/*$sort = */   	null, 
			/*$include = */	[], 
			/*$locale = */	null, //"fr_FR", 
			/*$status = */	null //["nin" => ["draft","public"]]
		);
		$array = $result->toArray();
		foreach ($array as $entity)
		{
			if(!array_key_exists($attribute->code, $entity['fields']))
			{
				continue;
			}
			foreach ($entity['fields'][$attribute->code] as $locale => $value)
			{
				$this->assertTrue($value === null);
			}
		}
	}

	public function testDeleteByAttributeSet()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();
		$repo->refreshIndex();
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$command = $app->make(AttributeSetFetchCommand::class);
		$command->setId(1);
		$attributeSet = $bus->dispatch($command);

		$result = $repo->deleteByAttributeSet($attributeSet);
		$repo->refreshIndex();
		$result = $repo->get();
		$array = $result->toArray();

		foreach ($array as $entity)
		{
			$this->assertFalse($entity['attribute_set_id'] === $attributeSet->id);
		}
	}

	public function testDeleteByEntityType()
	{
		fwrite(STDOUT, __METHOD__ . "\n");

		$app = $this->createApplication();
		$repo = $app->make('Congraph\EntityElastic\Repositories\EntityRepository');
		$this->elasticSeeder->up();
		$repo->refreshIndex();
		$bus = $app->make('Congraph\Core\Bus\CommandDispatcher');

		$command = $app->make(EntityTypeFetchCommand::class);
		$command->setId(1);
		$entityType = $bus->dispatch($command);

		$result = $repo->deleteByEntityType($entityType);
		$repo->refreshIndex();
		$result = $repo->get();
		$array = $result->toArray();
		// $this->d->dump($array);
		
		foreach ($array as $entity)
		{
			$this->assertFalse($entity['entity_type_id'] === $entityType->id);
		}
	}
}
