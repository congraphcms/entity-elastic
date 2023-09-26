<?php 
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Fields\Relation;

use Congraph\EntityElastic\Fields\AbstractFieldHandler;
use Congraph\Eav\Managers\AttributeManager;
use Elasticsearch\ClientBuilder;
use Congraph\Eav\Facades\MetaData;
use Congraph\Core\Facades\Trunk;
use stdClass;
use Congraph\Eav\Repositories\EntityRepository as EntitySQLRepository;
use Congraph\EntityElastic\Repositories\EntityRepository as EntityElasticRepository;


/**
 * RelationFieldHandler class
 *
 * Responsible for handling relation field types
 *
 *
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class RelationFieldHandler extends AbstractFieldHandler
{


    /**
     * Entity ES Repository
     *
     * @var \Congraph\EntityElastic\Repositories\EntityRepository
     */
    protected $esRepository;

    /**
     * Entity MySQL Repository
     *
     * @var \Congraph\Eav\Repositories\EntityRepository
     */
    protected $sqlRepository;

    /**
     * Create new RelationFieldHandler
     *
     * @param Illuminate\Database\Connection 			$db
     * @param Congraph\Eav\Managers\AttributeManager 	$attributeManager
     * @param string 									$table
     *
     * @return void
     */
    public function __construct(
      Client $elasticClient,
      AttributeManager $attributeManager,
      EntityElasticRepository $esRepository,
      EntitySQLRepository $sqlRepository
  ) {
      parent::__construct($elasticClient, $attributeManager);
      $this->esRepository = $esRepository;
      $this->sqlRepository = $sqlRepository;
  }

    /**
     * Parse value for database input
     *
     * @param mixed $value
     * @param object $attribute
     *
     * @return boolean
     */
    public function parseValue($value, $attribute, $locale, $params, $entity)
    {
        if (empty($value)) {
            return null;
        }
        if (is_array($value)) {
            $params = [
                'index' => $this->indexName,
                'id' => $value['id']
            ];

            if (!config('cb.elastic.indexing')) {
                $rawRelation = $this->client->get($params);
                $source = $rawRelation['_source'];
            } else {
                $result = $this->sqlRepository->fetch($value['id']);
                $source = $this->esRepository->parseEntityForES($result->toArray());
            }

            $data = [
                'id' => intval($value['id']),
                'type' => 'entity',
                'attribute_set_id' => $source['attribute_set_id'],
                'entity_type_id' => $source['entity_type_id']
            ];
            $value = $data;
        }
        
        return $value;
    }

    /**
     * Format value for output
     *
     * @param mixed $value
     * @param object $attribute
     *
     * @return boolean
     */
    public function formatValue($value, $attribute, $status, $locale, $localeCodes)
    {
        if (empty($value)) {
            return null;
        }
        $relation = new stdClass();
        $relation->id = $value['id'];
        $relation->type = 'entity';
        return $relation;
    }

    /**
     * Add filters to query for field
     *
     * @param object $query
     * @param object $attribute
     * @param $filter
     *
     * @return boolean
     */
    public function filterEntities($query, $attribute, $filter, $locale = null, $localeCodes = [])
    {
        $code = $attribute->code;

        if ($attribute->localized) {
            if ($locale) {
                $code = $code . '__' . $locale->code;
            } else {
                $code = $code . '__*';
            }
        }

        if (! is_array($filter)) {
            $filter = intval($filter);
            $query = $this->addTermQuery($query, 'fields.' . $code . '.id', $filter);
        } else {
            $query = $this->parseFilterOperator($query, 'fields.' . $code . '.id', $filter);
        }

        return $query;
    }

    /**
     * Handle File Delete
     *
     * @return void
     */
    public function onEntityDelete($command, $result)
    {
        $query = $this->createEmptyQuery($this->indexName);
        $attributes = MetaData::getAttributes();
        $fieldKeys = [];
        foreach ($attributes as $attribute) {
            $attributeSettings = $this->attributeManager->getFieldTypes()[$attribute->field_type];
            if (get_class($this) != $attributeSettings['elastic_handler']) {
                continue;
            }

            if (!$attribute->localized) {
                $fieldKeys[] = $attribute->code;
                continue;
            }

            foreach (MetaData::getLocales() as $locale) {
                $fieldKeys[] = $attribute->code . '__' . $locale->code;
            }
        }

        foreach ($fieldKeys as $fieldKey) {
            $query = $this->addShouldTermQuery($query, 'fields.' . $fieldKey . '.id', $command->id);
        }

        $rawDocuments = $this->client->search($query);

        $changed = false;

        foreach ($rawDocuments['hits']['hits'] as $document) {
            $id = $document['_id'];
            if ($id == $command->id) {
                continue;
            }
            $body = $document['_source'];

            foreach ($body['fields'] as $key => &$value) {
                if (!in_array($key, $fieldKeys)) {
                    continue;
                }

                if (!is_array($value)) {
                    $value = null;
                    continue;
                }

                if (array_key_exists('id', $value) && $value['id'] == $command->id) {
                    $value = null;
                    continue;
                }

                $itemsToRemove = [];

                foreach ($value as $item) {
                    if (array_key_exists('id', $item) && $item['id'] == $command->id) {
                        $itemsToRemove[] = $item;
                        continue;
                    }
                }

                foreach ($itemsToRemove as $i) {
                    $index = array_search($i, $value);
                    unset($value[$index]);
                }

                $value = array_values($value);
            }

            $params = [];
            $params['index'] = $this->indexName;
            $params['id'] = $id;
	        $params['body'] = ['doc' => $body];

            $this->client->update($params);
            $changed = true;
        }

        if ($changed) {
            Trunk::forgetType('entity');
        }
    }

    /**
     * Clean all related values and set entries for given attribute set
     *
     * Takes attribute set that needs to be deleted,
     * and deletes all related values and set entries
     *
     * @param object $attributeSet
     * @param object $attribute
     *
     * @todo Check if there is need for returning false or there will be an exception if something goes wrong
     */
    public function onAttributeSetDelete($attributeSet, $attribute)
    {
    }

    /**
     * Clean all related values for given entity type
     *
     * Takes attribute set that needs to be deleted,
     * and deletes all related values and set entries
     *
     * @param object $entityType
     * @param object $attribute
     *
     * @todo Check if there is need for returning false or there will be an exception if something goes wrong
     */
    public function onEntityTypeDelete($entityType, $attribute)
    {
    }
}
