<?php

use Congraph\Core\Exceptions\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Elasticsearch\ClientBuilder;

require_once(__DIR__ . '/../TestCase.php');

class AsciifoldingTest extends TestCase
{

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
