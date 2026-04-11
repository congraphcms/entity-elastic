<?php

use Carbon\Carbon;
use Congraph\Eav\Commands\Entities\EntityCreateCommand;
use Congraph\Eav\Commands\Entities\EntityUpdateCommand;
use Congraph\EntityElastic\Repositories\EntityRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use OpenSearch\Client;

require_once(__DIR__ . '/../TestCase.php');

class IndexAllCommandTest extends TestCase
{
	private Client $client;
	private EntityRepository $elasticRepository;
	private string $indexName;

	protected function setUp(): void
	{
		parent::setUp();

		$this->client = $this->app->make(Client::class);
		$this->elasticRepository = $this->app->make(EntityRepository::class);
		$this->indexName = Config::get('cb.elastic.index_prefix') . 'entities';
	}

	public function testIndexAllCommandIndexesAllEntitiesIntoAnEmptyIndex()
	{
		$this->recreateIndex();

		$expectedTotal = DB::table('entities')->count();

		$this->artisan('congraph:es:index-all')
			->assertExitCode(0);

		$this->assertSame($expectedTotal, $this->documentCount());
		$this->assertFalse((bool) Config::get('cb.elastic.indexing'));
		$this->assertSame(1, $this->fetchRawDocument(1)['id']);
		$this->assertSame(4, $this->fetchRawDocument(4)['id']);
	}

	public function testIndexAllCommandIndexesEntitiesAcrossMultipleBatches()
	{
		for ($i = 0; $i < 8; $i++) {
			$this->createFieldEntity([
				'test_text_attribute' => 'batch-' . $i,
			]);
		}

		$this->recreateIndex();

		$expectedTotal = DB::table('entities')->count();
		$latestId = (int) DB::table('entities')->max('id');

		$this->artisan('congraph:es:index-all')
			->assertExitCode(0);

		$this->assertSame($expectedTotal, $this->documentCount());
		$this->assertSame($latestId, $this->fetchRawDocument($latestId)['id']);
	}

	public function testIndexAllCommandIndexesNodeFieldsWhenReferencedEntityWasCreatedLater()
	{
		$parent = $this->createFieldEntity([
			'test_text_attribute' => 'parent-node',
		]);

		$child = $this->createFieldEntity([
			'test_text_attribute' => 'child-node',
		]);

		$this->updateFieldEntity($parent->id, [
			'test_node_attribute' => ['id' => $child->id, 'type' => 'entity'],
		]);

		$this->setEntityCreatedAt($parent->id, '2026-01-01 09:00:00');
		$this->setEntityCreatedAt($child->id, '2026-01-01 10:00:00');

		$this->recreateIndex();

		$this->artisan('congraph:es:index-all')
			->assertExitCode(0);

		$parentDocument = $this->fetchRawDocument($parent->id);

		$this->assertArraySubset([
			'id' => $parent->id,
			'fields' => [
				'test_node_attribute' => [
					'id' => $child->id,
					'fields' => [
						'test_text_attribute' => 'child-node',
					],
				],
			],
		], $parentDocument);
	}

	public function testIndexAllCommandIndexesNodeCollectionFieldsWhenReferencedEntitiesWereCreatedLater()
	{
		$parent = $this->createFieldEntity([
			'test_text_attribute' => 'parent-node-collection',
		]);

		$firstChild = $this->createFieldEntity([
			'test_text_attribute' => 'child-node-collection-1',
		]);

		$secondChild = $this->createFieldEntity([
			'test_text_attribute' => 'child-node-collection-2',
		]);

		$this->updateFieldEntity($parent->id, [
			'test_nodecollection_attribute' => [
				['id' => $firstChild->id, 'type' => 'entity'],
				['id' => $secondChild->id, 'type' => 'entity'],
			],
		]);

		$this->setEntityCreatedAt($parent->id, '2026-01-01 09:00:00');
		$this->setEntityCreatedAt($firstChild->id, '2026-01-01 10:00:00');
		$this->setEntityCreatedAt($secondChild->id, '2026-01-01 11:00:00');

		$this->recreateIndex();

		$this->artisan('congraph:es:index-all')
			->assertExitCode(0);

		$parentDocument = $this->fetchRawDocument($parent->id);

		$this->assertCount(2, $parentDocument['fields']['test_nodecollection_attribute']);
		$this->assertArraySubset([
			'id' => $firstChild->id,
			'fields' => [
				'test_text_attribute' => 'child-node-collection-1',
			],
		], $parentDocument['fields']['test_nodecollection_attribute'][0]);
		$this->assertArraySubset([
			'id' => $secondChild->id,
			'fields' => [
				'test_text_attribute' => 'child-node-collection-2',
			],
		], $parentDocument['fields']['test_nodecollection_attribute'][1]);
	}

	private function createFieldEntity(array $fields)
	{
		$command = $this->app->make(EntityCreateCommand::class);
		$command->setParams([
			'entity_type_id' => 4,
			'attribute_set_id' => 4,
			'locale' => 'en_US',
			'fields' => $fields,
		]);

		return $this->dispatch($command);
	}

	private function updateFieldEntity(int $id, array $fields): mixed
	{
		$command = $this->app->make(EntityUpdateCommand::class);
		$command->setId($id);
		$command->setParams([
			'locale' => 'en_US',
			'fields' => $fields,
		]);

		return $this->dispatch($command);
	}

	private function setEntityCreatedAt(int $id, string $createdAt): void
	{
		DB::table('entities')
			->where('id', $id)
			->update([
				'created_at' => Carbon::parse($createdAt)->toDateTimeString(),
				'updated_at' => Carbon::parse($createdAt)->addMinute()->toDateTimeString(),
			]);
	}

	private function recreateIndex(): void
	{
		if ($this->client->indices()->exists(['index' => $this->indexName])) {
			$this->client->indices()->delete(['index' => $this->indexName]);
		}

		$this->client->indices()->create([
			'index' => $this->indexName,
			'body' => Config::get('cb.elastic.default_index_mappings'),
		]);

		$this->elasticRepository->refreshIndex();
	}

	private function fetchRawDocument(int $id): array
	{
		$result = $this->client->get([
			'index' => $this->indexName,
			'id' => $id,
		]);

		return $result['_source'];
	}

	private function documentCount(): int
	{
		$result = $this->client->search([
			'index' => $this->indexName,
			'body' => [
				'query' => [
					'match_all' => (object) [],
				],
			],
			'size' => 0,
		]);

		$total = $result['hits']['total'];

		return (int) (is_array($total) ? $total['value'] : $total);
	}
}
