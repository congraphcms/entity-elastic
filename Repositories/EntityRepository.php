<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Repositories;

use Carbon\Carbon;
use Congraph\Contracts\Eav\FieldHandlerFactoryContract;
use Congraph\Core\Exceptions\Exception;
use Congraph\Core\Exceptions\NotFoundException;
use Congraph\Core\Exceptions\BadRequestException;
use Congraph\Core\Facades\Trunk;
// use Congraph\Core\Repositories\AbstractRepository;
use Congraph\Core\Repositories\Collection;
use Congraph\Core\Repositories\Model;
use Congraph\Core\Repositories\UsesCache;
use Congraph\Eav\Managers\AttributeManager;

use Congraph\EntityElastic\Traits\ElasticQueryBuilderTrait;
use Congraph\EntityElastic\Services\EntityFormater;

use Congraph\Eav\Facades\MetaData;


use Elasticsearch\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

use stdClass;

/**
 * EntityElasticRepository class
 *
 * Repository for entity elastic database queries
 *
 * @uses        Illuminate\Database\Connection
 * @uses        Congraph\Core\Repository\AbstractRepository
 * @uses        Congraph\Contracts\Eav\AttributeHandlerFactoryContract
 * @uses        Congraph\Eav\Managers\AttributeManager
 *
 * @author      Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright   Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package     congraph/entity-elastic
 * @since       0.1.0-alpha
 * @version     0.1.0-alpha
 */
class EntityRepository implements EntityRepositoryContract //, UsesCache
{
    use ElasticQueryBuilderTrait;

    /**
     * Factory for field handlers,
     * makes appropriate field handler depending on attribute data type
     *
     * @var \Congraph\Contracts\Eav\FieldHandlerFactoryContract
     */
    protected $fieldHandlerFactory;


    /**
     * Helper for attributes
     *
     * @var \Congraph\Eav\Managers\AttributeManager
     */
    protected $attributeManager;

    /**
     * Service for formating entities
     *
     * @var \Congraph\EntityElastic\Services\EntityFormater
     */
    protected $formater;

    /**
     * Elasticsearch client
     *
     * @var \Elasticsearch\Client
     */
    protected $client;

    protected static $memory = [];

    protected $indexName;



    /**
     * Create new EntityRepository
     *
     * @param Elasticsearch\ClientBuilder $elasticClientBuilder
     * @param Congraph\Eav\Handlers\AttributeHandlerFactoryContract $attributeHandlerFactory
     * @param Congraph\Eav\Managers\AttributeManager $attributeManager
     * @param Congraph\EntityElastic\Services\EntityFormater $entityFormater
     *
     * @return void
     */
    public function __construct(
        Client $elasticClient,
        FieldHandlerFactoryContract $fieldHandlerFactory,
        AttributeManager $attributeManager,
        EntityFormater $entityFormater
    ) {

        // Inject dependencies
        $this->fieldHandlerFactory = $fieldHandlerFactory;
        $this->attributeManager = $attributeManager;
        $this->formater = $entityFormater;
        $this->client = $elasticClient;

        $prefix = Config::get('cb.elastic.index_prefix');
        $this->indexName = $prefix . 'entities';

        $indexParams = [
            'index' => $this->indexName
        ];

        if (!$this->client->indices()->exists($indexParams)) {
            $params = [
                'index' => $this->indexName,
                'body' => Config::get('cb.elastic.default_index_mappings')
            ];
            $this->client->indices()->create($params);
            $this->refreshIndex();
        }
    }

    public function onEntityCreated($command, $result)
    {

        $this->create($command->params, $result);
    }

    public function onEntityUpdated($command, $result)
    {
        try {
            $result = $this->fetchRaw($command->id);
        } catch (NotFoundException $e) {
            $this->create($command->params, $result);
            return;
        }
        $this->update($command->id, $command->params, $result);
    }

    public function onEntityDeleted($command, $result)
    {
        try {
            $res = $this->fetchRaw($command->id);
        } catch (NotFoundException $e) {
            return;
        }

        $this->delete($command->id);
    }

    public function onAttributeDeleted($command, $result)
    {
        $this->deleteByAttribute($result);
    }

    public function onAttributeSetDeleted($command, $result)
    {
        $this->deleteByAttributeSet($result);
    }

    public function onEntityTypeDeleted($command, $result)
    {
        $this->deleteByEntityType($result);
    }


    public function getIndices()
    {
        $response = $this->client->indices()->getSettings();
        return $response;
    }

    public function indexExists($indexName)
    {
        $params = [
            'index' => $indexName
        ];
        return $this->client->indices()->exists($params);
    }

    public function getSettings($indexName)
    {
        $params = [
            'index' => $indexName
        ];
        $response = $this->client->indices()->getSettings($params);
        return $response;
    }

    public function getMappings($indexName)
    {
        $params = [
            'index' => $indexName
        ];
        $response = $this->client->indices()->getMapping($params);
        return $response;
    }

    public function deleteIndex($indexName)
    {
        $params = [
            'index' => $indexName
        ];
        $response = $this->client->indices()->delete($params);
        return $response;
    }

    public function refreshIndex()
    {
        $params = [
            "index" => $this->indexName
        ];

        $this->client->indices()->refresh($params);
    }



    /**
     * Create new entity
     *
     * @param array $model - entity params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function create($model)
    {
        if ((!$model instanceof Model)) {
            throw new \Exception('NOT A MODEL');
        }
        $params = [];
        $params['index'] = $this->indexName;

        $body = [];

        $body['created_at'] = $model->created_at->tz('UTC')->getTimestamp();
        $body['updated_at'] = $model->updated_at->tz('UTC')->getTimestamp();
        $model = $model->toArray();
        $fields = [];

        // if (array_key_exists('id', $model)) {
        $params['id'] = $body['id'] = $model['id'];
        // }

        if (array_key_exists('fields', $model) && is_array($model['fields'])) {
            $fields = $model['fields'];
        }

        $body['entity_type_id'] = $model['entity_type_id'];
        $body['attribute_set_id'] = $model['attribute_set_id'];

        $body['fields'] = [];
        $body['status'] = [];

        $locale = false;
        $localeCodes = [null];
        $locale_id = null;

        if (! empty($model['locale'])) {
            list($locale, $localeCodes) = $this->parseLocale($model['locale']);
            $locale_id = $locale->id;
        }

        $status = null;
        if (! empty($model['status'])) {
            $status = $model['status'];
        }

        $attributeSet = MetaData::getAttributeSetById($body['attribute_set_id']);

        foreach ($attributeSet['attributes'] as $setAttribute) {
            $attribute = MetaData::getAttributeById($setAttribute->id);
            $code = $attribute->code;
            $fieldHandler = $this->fieldHandlerFactory->make($attribute->field_type);

            if (!$attribute->localized) {
                if (array_key_exists($code, $fields)) {
                    $value = $fields[$code];
                } else {
                    $value = $attribute->default_value;
                }

                $value = $fieldHandler->prepareForElastic($value, $attribute, $locale_id, $model, null);
                $body['fields'][$code] = $value;
                continue;
            }

            foreach (MetaData::getLocales() as $l) {
                if ($locale) {
                    if ($locale->id == $l->id) {
                        $value = (array_key_exists($code, $fields))?$fields[$code]:$attribute->default_value;
                        $value = $fieldHandler->prepareForElastic($value, $attribute, $l->id, $model, null);
                        $body['fields'][$code . '__' . $l->code] = $value;
                        continue;
                    }

                    $value = $attribute->default_value;
                    $value = $fieldHandler->prepareForElastic($value, $attribute, $l->id, $model, null);
                    $body['fields'][$code . '__' . $l->code] = $value;
                    continue;
                }

                if (array_key_exists($code, $fields) && array_key_exists($l->code, $fields[$code])) {
                    $value = $fields[$code][$l->code];
                    $value = $fieldHandler->prepareForElastic($value, $attribute, $l->id, $model, null);
                    $body['fields'][$code . '__' . $l->code] = $value;
                    continue;
                }

                $value = $attribute->default_value;
                $value = $fieldHandler->prepareForElastic($value, $attribute, $l->id, $model, null);
                $body['fields'][$code . '__' . $l->code] = $value;
            }
        }

        $entityType = MetaData::getEntityTypeById($body['entity_type_id']);
        $body['localized'] = !!$entityType->localized;
        $body['localized_workflow'] = !!$entityType->localized_workflow;


        if (isset($status)) {
            if (is_array($status)) {
                $point = [];
                foreach ($status as $loc => $value) {
                    $point[$loc] = MetaData::getWorkflowPointByStatus($value);
                }
            } else {
                $point = MetaData::getWorkflowPointByStatus($status);
            }
        } else {
            $point = MetaData::getWorkflowPointById($entityType->default_point->id);
        }

        $localeCodes = [];
        if (! $entityType->localized_workflow) {
            $localeCodes[] = null;
        } else {
            if (empty($locale)) {
                foreach (MetaData::getLocales() as $l) {
                    $localeCodes[] = $l->code;
                }
            } else {
                $localeCodes[] = $locale->code;
            }
        }

        foreach ($localeCodes as $lc) {
            if (is_array($point)) {
                $s = $point[$lc]->status;
            } else {
                $s = $point->status;
            }
            $statusObj = [
                'status' => $s,
                'locale' => $lc,
                'state' => 'active',
                'scheduled_at' => null,
                'created_at' => $body['created_at'],
                'updated_at' => $body['updated_at']
            ];

            $body['status'][] = $statusObj;
        }

        $params['body'] = $body;

        try {
            $response = $this->client->index($params);
		} catch (\Exception $e) {
			printf ("Exception: %s\n", $e->getMessage());
            var_dump($this->client->transport->getLastConnection()->getLastRequestInfo()["request"]);
		}


        Trunk::forgetType('entity');

        $entity = $this->fetch($response['_id'], [], $locale_id);

        return $entity;
    }

    /**
     * Update entity and its fields
     *
     * @param int $id - entity ID
     * @param array $model - entity params
     *
     * @return mixed
     *
     * @throws Exception
     *
     * @todo enable attribute set change for entity
     */
    public function update($id, $model, $result = null)
    {
        $params = [];
        $params['index'] = $this->indexName;
        $params['id'] = $id;

        // fetch raw document from database
        $rawDocument = $this->fetchRaw($id);

        $body = $rawDocument['_source'];

        $changed = false;

        $fields = array();
        if (! empty($model['fields']) && is_array($model['fields'])) {
            $fields = $model['fields'];
        }

        $locale = false;
        $localeCodes = [null];
        $locale_id = null;

        if (! empty($model['locale'])) {
            list($locale, $localeCodes) = $this->parseLocale($model['locale']);
            $locale_id = $locale->id;
        }

        $status = null;
        if (! empty($model['status'])) {
            $status = $model['status'];
        }

        // update fields
        $attributeSet = MetaData::getAttributeSetById($body['attribute_set_id']);
        foreach ($attributeSet['attributes'] as $setAttribute) {
            $attribute = MetaData::getAttributeById($setAttribute->id);
            $code = $attribute->code;
            $fieldHandler = $this->fieldHandlerFactory->make($attribute->field_type);

            if (!array_key_exists($code, $fields)) {
                continue;
            }

            if (!$attribute->localized || $locale) {
                $fieldName = $code;
                if ($attribute->localized) {
                    $fieldName .= '__' . $locale->code;
                }
                $value = $fieldHandler->prepareForElastic($fields[$code], $attribute, $locale_id, $model, $rawDocument);
                if (isset($body['fields'][$fieldName]) && $value === $body['fields'][$fieldName]) {
                    continue;
                }

                $body['fields'][$fieldName] = $value;
                $changed = true;
                continue;
            }

            foreach (MetaData::getLocales() as $l) {
                $fieldName = $code . '__' . $l->code;
                // var_dump($rawDocument);

                if (!array_key_exists($l->code, $fields[$code])) {
                    continue;
                }
                // var_dump('updating field - ' . $fieldName);

                $value = $fieldHandler->prepareForElastic($fields[$code][$l->code], $attribute, $l->id, $model, $rawDocument);
                // var_dump($value);
                // var_dump($body['fields'][$fieldName]);
                if ($value === $body['fields'][$fieldName]) {
                    continue;
                }

                $body['fields'][$fieldName] = $value;
                $changed = true;
            }
        }

        $entityType = MetaData::getEntityTypeById($body['entity_type_id']);
        // update status

        if (isset($status)) {
            if (is_array($status)) {
                $point = [];
                foreach ($status as $loc => $value) {
                    $point[$loc] = MetaData::getWorkflowPointByStatus($value);
                }
            } else {
                $point = MetaData::getWorkflowPointByStatus($status);
            }

            $localeCodes = [];
            if (! $entityType->localized_workflow) {
                $localeCodes[] = null;
            } else {
                if (empty($locale)) {
                    if (is_array($status)) {
                        foreach ($status as $loc => $value) {
                            $localeCodes[] = $loc;
                        }
                    } else {
                        foreach (MetaData::getLocales() as $l) {
                            $localeCodes[] = $l->code;
                        }
                    }
                } else {
                    $localeCodes[] = $locale->code;
                }
            }

            foreach ($localeCodes as $lc) {
                if (is_array($point)) {
                    $s = $point[$lc]->status;
                } else {
                    $s = $point->status;
                }

                $statusChanged = false;
                $statusExists = false;

                foreach ($body['status'] as &$oldStatus) {
                    if ($oldStatus['state'] == 'active'
                        && $oldStatus['locale'] == $lc
                        && $oldStatus['status'] !== $s) {
                        $oldStatus['state'] = 'history';
                        $statusChanged = true;
                        $statusExists = true;
                        $changed = true;
                        continue;
                    }
                    if ($oldStatus['state'] == 'active' && $oldStatus['locale'] == $lc) {
                        $statusExists = true;
                    }
                }

                if (!$statusChanged && $statusExists) {
                    continue;
                }

                $changed = true;
                $statusObj = [
                    'status' => $s,
                    'locale' => $lc,
                    'state' => 'active',
                    'scheduled_at' => null
                ];

                // update updated_at
                if (!($result instanceof Model) || !is_integer($result->id) || empty($result->id)) {
                    $statusObj['created_at'] = $statusObj['updated_at'] = gmdate("U");
                } else {
                    $statusObj['created_at'] = $statusObj['updated_at'] = $result->updated_at->tz('UTC')->getTimestamp();
                }

                $body['status'][] = $statusObj;
            }
        }



        if ($changed) {
            // update updated_at
            if (!($result instanceof Model) || !is_integer($result->id) || empty($result->id)) {
                $body['updated_at'] = gmdate("U");
            } else {
                $body['updated_at'] = $result->updated_at->tz('UTC')->getTimestamp();
            }

            // update data in elastic
            $params['body'] = ['doc' => $body];
            $this->client->update($params);
        }

        Trunk::forgetType('entity');

        // fetch new document
        $entity = $this->fetch($id, [], $locale_id);
        // return new document
        return $entity;
    }

    /**
     * Delete entity and its attributes
     *
     * @param integer | array $ids - ID of entity that will be deleted
     *
     * @return boolean
     *
     * @throws InvalidArgumentException, Exception
     */
    public function delete($id)
    {
        $params = [];
        $params['index'] = $this->indexName;
        $params['id'] = $id;

        // get the entity
        $entity = $this->fetch($id);

        $this->client->delete($params);

        Trunk::forgetType('entity');
        return $entity;
    }

    /**
     * Delete locele for entity
     *
     * @param mixed $id entity id
     * @param mixed $locale_id  locale id
     *
     * @todo check if its last locale and delete whole entity if it is
     */
    public function deleteForLocale($id, $locale_id)
    {
        $params = [];
        $params['index'] = $this->indexName;
        $params['id'] = $id;

        $changed = false;

        // get the locale
        list($locale, $localeCodes) = $this->parseLocale($locale_id);

        // get the raw entity
        $rawDocument = $this->fetchRaw($id);
        $entity = $this->fetch($id, [], $locale_id);
        $body = $rawDocument['_source'];

        // update status
        foreach ($body['status'] as &$status) {
            if ($status['locale'] == $locale->code && $status['state'] == 'active') {
                $status['state'] = 'history';
                $changed = true;
            }
        }

        // find fields
        $sufix = '__' . $locale->code;
        $sufixLength = strlen($sufix);
        $fieldsForDelete = [];
        foreach ($body['fields'] as $key => $value) {
            if (substr($key, -$sufixLength) === $sufix) {
                $fieldsForDelete[] = $key;
            }
        }

        // remove fields
        foreach ($fieldsForDelete as $key) {
            unset($body['fields'][$key]);
            $changed = true;
        }

        if ($changed) {
            // update updated_at
            $body['updated_at'] = gmdate("U");
            // update data in elastic
	        $params['body'] = ['doc' => $body];

            $this->client->update($params);
        }

        Trunk::forgetType('entity');
        return $entity;
    }

    /**
     * Delete all entities for attribute set
     *
     * @param object $attributeSet
     *
     * @return void
     */
    public function deleteByAttribute($attribute)
    {
        $this->refreshIndex();
        if ($attribute->localized) {
            foreach (MetaData::getLocales() as $locale) {
                $query = $this->createEmptyQuery($this->indexName);
                unset($query['body']['size']);
                $query['body']['script'] = [];
                // $nested = $this->createNestedQuery('fields');
                $fieldKey = $attribute->code . '__' . $locale->code;
                $filterKey = 'fields.' . $fieldKey;
                $query = $this->addExistsQuery($query, $filterKey);
                // $query = $this->addNestedQuery($query, $nested);
                $query['body']['script']['source'] = "ctx._source.fields.remove(\"".$fieldKey."\")";
                $this->client->updateByQuery($query);
                $this->refreshIndex();
            }
        } else {
            $query = $this->createEmptyQuery($this->indexName);
            unset($query['body']['size']);
            $query['body']['script'] = [];
            // $nested = $this->createNestedQuery('fields');
            $fieldKey = $attribute->code;
            $filterKey = 'fields.' . $fieldKey;
            $query = $this->addExistsQuery($query, $filterKey);
            // $query = $this->addNestedQuery($query, $nested);
            $query['body']['script']['source'] = "ctx._source.fields.remove(\"".$fieldKey."\")";
            $this->client->updateByQuery($query);
        }

        Trunk::forgetType('entity');

        return true;
    }

    /**
     * Delete all entities for attribute set
     *
     * @param object $attributeSet
     *
     * @return void
     */
    public function deleteByAttributeSet($attributeSet)
    {
        $this->refreshIndex();

        $query = $this->createEmptyQuery($this->indexName);
        unset($query['body']['size']);
        $query = $this->addTermQuery($query, 'attribute_set_id', $attributeSet->id);
        $this->client->deleteByQuery($query);
        $this->refreshIndex();

        Trunk::forgetType('entity');

        return true;
    }

    /**
     * Delete all entities for entity type
     *
     * @param object $entityType
     *
     * @return void
     */
    public function deleteByEntityType($entityType)
    {
        $this->refreshIndex();

        $query = $this->createEmptyQuery($this->indexName);
        unset($query['body']['size']);
        $query = $this->addTermQuery($query, 'entity_type_id', $entityType->id);
        $this->client->deleteByQuery($query);
        $this->refreshIndex();

        Trunk::forgetType('entity');

        return true;
    }


    // ----------------------------------------------------------------------------------------------
    // GETTERS
    // ----------------------------------------------------------------------------------------------

    /**
     * Get entity by ID
     *
     * @param mixed $id
     * @param array $include
     * @param mixed $locale
     *
     * @return array
     */
    public function fetch($id, $include = [], $locale = null, $status = null)
    {
        $params = func_get_args();
        $params['function'] = __METHOD__;
        if (Trunk::has($params, 'entity')) {
            $result = Trunk::get($params, 'entity');
            $result->clearIncluded();
            $result->load($include);
            $meta = ['id' => $id, 'include' => $include, 'locale' => $locale, 'status' => $status];
            $result->setMeta($meta);
            return $result;
        }

        list($locale, $localeCodes) = $this->parseLocale($locale);

        $result = $this->fetchRaw($id);

        $result = $this->formater->formatEntity($result, $status, $locale, $localeCodes);

        $result = $this->decorateResult($result, $params);

        return $result;
    }

    public function fetchRaw($id)
    {
        $query = [
            'index' => $this->indexName,
            'id' => $id
        ];

        try {
            $result = $this->client->get($query);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            throw new NotFoundException(['Entity not found.']);
        }

        return $result;
    }

    /**
     * Get entities
     *
     * @return array
     */
    public function get($filter = [], $offset = 0, $limit = 0, $sort = [], $include = [], $locale = null, $status = null)
    {
        $params = func_get_args();
        $params['function'] = __METHOD__;
        if (Trunk::has($params, 'entity')) {
            $result = Trunk::get($params, 'entity');
            $result->clearIncluded();
            $result->load($include);
            $meta = ['id' => $id, 'include' => $include, 'locale' => $locale, 'status' => $status];
            $result->setMeta($meta);
            return $result;
        }

        list($locale, $localeCodes) = $this->parseLocale($locale);

        $query = $this->createEmptyQuery($this->indexName);

        $query = $this->queryStatus($query, $status, $locale, $localeCodes);
        $query = $this->queryPagination($query, $offset, $limit);
        list($query, $doSorting) = $this->queryFiltering($query, $filter, $locale, $localeCodes);
        if ($doSorting) {
            $query = $this->querySorting($query, $sort, $locale, $localeCodes);
        }




        $result = $this->client->search($query);
        // foreach ($result['hits']['hits'] as $value) {
        //     var_dump($value['_source']);
        // }
        // echo json_encode([$query, $result]);
        // die();

        $total = $result['hits']['total'];

        $result = $this->formater->formatEntities($result, $status, $locale, $localeCodes);

        $result = $this->decorateResult($result, $params, $total);

        return $result;
    }

    protected function queryFiltering($query, $filters, $localeFilter, $localeCodes)
    {
        $fieldFilters = [];
        $status = null;
        $public = false;
        $fulltextSearch = null;
        $doSorting = true;



        foreach ($filters as $key => $filter) {
            if ($key == 's') {
                $fulltextSearch = strval($filter);
                continue;
            }

            if ($key == 'entity_type' || $key == 'type') {
                $key = 'entity_type_id';
                $filter = $this->castCodesToIDs($filter, 'entityType');
            }


            if (substr($key, 0, 7) === "fields.") {
                $code = substr($key, 7);
                $fieldFilters[$code] = $filter;
                continue;
            }

            if (!is_array($filter)) {
                $query = $this->addTermQuery($query, $key, $filter);
                continue;
            }

            $query = $this->parseFilterOperator($query, $key, $filter);
        }
        if (! empty($fieldFilters)) {
            // $nested = $this->createNestedQuery('fields');

            foreach ($fieldFilters as $code => $filter) {
                $attribute = MetaData::getAttributeByCode($code);
                $query = $this->parseFieldFilter($query, $filter, $attribute, $localeFilter, $localeCodes);
            }

            // $query = $this->addNestedQuery($query, $nested);
        }

        if (! empty($fulltextSearch)) {
            $query = $this->parseFulltextSearch($query, $fulltextSearch, $localeFilter);
            $doSorting = false;
        }

        return [$query, $doSorting];
    }

    protected function castCodesToIDs($codes, $type)
    {
        if (!is_array($codes)) {
            $object = $this->getObjectByTypeAndCode($type, $codes);
            if (!$object) {
                throw new BadRequestException("Invalid " . $type . " code:" . $codes);
            }
            return $object->id;
        }

        foreach ($codes as $operator => &$value) {
            if ($operator === 'in' || $operator === 'nin') {
                if (!is_array($value)) {
                    $value = explode(',', $value);
                }

                foreach ($value as &$item) {
                    $item = ltrim(rtrim($item));

                    $object = $this->getObjectByTypeAndCode($type, $item);
                    if (!$object) {
                        throw new BadRequestException("Invalid " . $type . " code:" . $item);
                    }
                    $item = $object->id;
                }
                continue;
            }

            $value = ltrim(rtrim($value));

            $object = $this->getObjectByTypeAndCode($type, $value);
            if (!$object) {
                throw new BadRequestException("Invalid " . $type . " code:" . $value);
            }
            $value = $object->id;
        }

        return $codes;
    }

    protected function getObjectByTypeAndCode($type, $code)
    {
        switch ($type) {
            case 'attribute':
                return MetaData::getAttributeByCode($code);
                break;
            case 'attributeSet':
                return MetaData::getAttributeSetByCode($code);
                break;
            case 'entityType':
                return MetaData::getEntityTypeByCode($code);
                break;
            case 'locale':
                return MetaData::getLocaleByCode($code);
                break;
            default:
                throw new BadRequestException("Invalid type: " . $type);
                break;
        }
    }

    protected function parseFulltextSearch($query, $fulltextSearch, $localeFilter)
    {
        $attributes = MetaData::getAttributes();
        $fields = [];
        $nodes = [];
        $nodeFields = [];

        foreach ($attributes as $attribute) {
            if (in_array($attribute->field_type, ['node', 'node_collection'])) {
                $nodes[] = $attribute;
                $nodeFields[strval($attribute->search_boost)] = [];
            }
        }


        foreach ($attributes as $attribute) {
            // var_dump($attribute->search_boost);
            if (in_array($attribute->field_type, ['text', 'tags', 'compound']) && $attribute->searchable) {
                $boostKey = strval($attribute->search_boost);
                if (!isset($fields[$boostKey])) {
                    $fields[$boostKey] = [];
                }
                foreach ($nodes as $node) {
                    $nodeBoostKey = strval($node->search_boost * $attribute->search_boost);
                    if (!isset($nodeFields[$nodeBoostKey])) {
                        $nodeFields[$nodeBoostKey] = [];
                    }
                }


                if (!$attribute->localized) {
                    $fields[$boostKey][] = 'fields.' . $attribute->code;
                    foreach ($nodes as $node) {
                        $nodeBoostKey = strval($node->search_boost * $attribute->search_boost);
                        $nodeFields[$nodeBoostKey][] = 'fields.' . $node->code . '.fields.' . $attribute->code;
                    }
                    continue;
                }

                if ($localeFilter) {
                    $fields[$boostKey][] = 'fields.' . $attribute->code . '__' . $localeFilter->code;
                    foreach ($nodes as $node) {
                        $nodeBoostKey = strval($node->search_boost * $attribute->search_boost);
                        $nodeFields[$nodeBoostKey][] = 'fields.' . $node->code . '.fields.' . $attribute->code . '__' . $localeFilter->code;
                    }
                    continue;
                }


                $fields[$boostKey][] = 'fields.' . $attribute->code . '__*';
                foreach ($nodes as $node) {
                    $nodeBoostKey = strval($node->search_boost * $attribute->search_boost);
                    $nodeFields[$nodeBoostKey][] = 'fields.' . $node->code . '.fields.' . $attribute->code . '__*';
                }
            }
        }
        foreach ($fields as $boostKey => $values) {
            $boost = floatval($boostKey);
            if (!empty($values)) {
                $query = $this->addMultiMatchQuery($query, $values, $fulltextSearch, 'cross_fields', true, $boost * 1);
                // $query = $this->addMultiMatchQuery($query, $values, $fulltextSearch, 'phrase_prefix', true, $boost * 2);
                $query = $this->addMultiMatchQuery($query, $values, $fulltextSearch, 'phrase', true, $boost * 3);
            }
        }

        foreach ($nodeFields as $boostKey => $values) {
            $boost = floatval($boostKey);
            if (!empty($values)) {
                $query = $this->addMultiMatchQuery($query, $values, $fulltextSearch, 'cross_fields', true, $boost * 1);
                // $query = $this->addMultiMatchQuery($query, $values, $fulltextSearch, 'phrase_prefix', true, $boost * 2);
                $query = $this->addMultiMatchQuery($query, $values, $fulltextSearch, 'phrase', true, $boost * 3);
            }
        }

        // account for date relevance
        $useDateRelevance = Config::get('cb.elastic.use_date_relevance');
        if (!$useDateRelevance) {
            return $query;
        }

        $dateRelevanceInterval = Config::get('cb.elastic.date_relevance_interval');
        $dateRelevanceIntervalCount = Config::get('cb.elastic.date_relevance_interval_count');
        $dateRelevanceBoostStep = Config::get('cb.elastic.date_relevance_boost_step');

        for ($i = 1; $i <= $dateRelevanceIntervalCount; $i++) {
            $dif = $i * $dateRelevanceInterval;
            $boost = ($dateRelevanceIntervalCount + 1 - $i) * $dateRelevanceBoostStep;

            $date = new \DateTime("-$dif day");

            $query = $this->addRangeQuery($query, 'created_at', $date->getTimestamp(), 'gte', true, $boost);
        }

        // var_dump($nodeFields);
        // var_dump($nodes);

        // var_dump($query);

        return $query;
    }

    protected function parseFieldFilter($query, $filter, $attribute, $localeFilter, $localeCodes)
    {
        $fieldHandler = $this->fieldHandlerFactory->make($attribute->field_type);
        $query = $fieldHandler->filterEntities($query, $attribute, $filter, $localeFilter, $localeCodes);

        return $query;
    }

    protected function querySorting($query, $sort, $localeFilter, $localeCodes)
    {
        if (! empty($sort)) {
            $sort = (is_array($sort))? $sort: [$sort];


            foreach ($sort as $sortCriteria) {
                $sortDirection = 'asc';

                if ($sortCriteria[0] === '-') {
                    $sortCriteria = substr($sortCriteria, 1);
                    $sortDirection = 'desc';
                }

                if (substr($sortCriteria, 0, 7) === "fields.") {
                    $code = substr($sortCriteria, 7);
                    $attribute = MetaData::getAttributeByCode($code);
                    if (in_array($attribute->field_type, ['text', 'tags', 'compound'])) {
                        if (!$attribute->localized) {
                            $sortCriteria = $sortCriteria . '.keyword';
                        } else {
                            if ($localeFilter) {
                                $sortCriteria = $sortCriteria . '__' . $localeFilter->code . '.keyword';
                            } else {
                                $sortCriteria = $sortCriteria . '__' . $localeCodes[0] . '.keyword';
                            }
                        }
                    }

                    $sortDirection = [
                        'order' => $sortDirection,
                        // 'nested_path' => 'fields'
                    ];
                }

                $query = $this->addSort($query, $sortCriteria, $sortDirection);
            }
        }

        return $query;
    }

    protected function queryStatus($query, $statusFilter, $localeFilter, $localeCodes)
    {
        $nested = $this->createNestedQuery('status');
        if (empty($statusFilter)) {
            if (!empty($localeFilter)) {
                $nested = $this->addTermQuery($nested, 'status.locale', $localeFilter->code);
                $nested = $this->addTermQuery($nested, 'status.state', 'active');
                return $this->addNestedQuery($query, $nested);
            }

            return $query;
        }

        if (!is_array($statusFilter)) {
            $nested = $this->addTermQuery($nested, 'status.status', $statusFilter);
            $nested = $this->addTermQuery($nested, 'status.state', 'active');
            $nested = $this->addLocaleQuery($nested, $localeFilter);
            return $this->addNestedQuery($query, $nested);
        } else {
            foreach ($statusFilter as $operator => $value) {
                switch (true) {
                    case ($operator === 'e'):
                        $nested = $this->addTermQuery($nested, 'status.status', $value);
                        $nested = $this->addTermQuery($nested, 'status.state', 'active');
                        $nested = $this->addLocaleQuery($nested, $localeFilter);
                        return $this->addNestedQuery($query, $nested);
                    case ($operator === 'ne'):
                        $nested = $this->addNotTermQuery($nested, 'status.status', $value);
                        $nested = $this->addTermQuery($nested, 'status.state', 'active');
                        $nested = $this->addLocaleQuery($nested, $localeFilter);
                        return $this->addNestedQuery($query, $nested);
                    case ($operator === 'in'):
                        $nested = $this->addTermsQuery($nested, 'status.status', $value);
                        $nested = $this->addTermQuery($nested, 'status.state', 'active');
                        $nested = $this->addLocaleQuery($nested, $localeFilter);
                        return $this->addNestedQuery($query, $nested);
                    case ($operator === 'nin'):
                        $nested = $this->addNotTermsQuery($nested, 'status.status', $value);
                        $nested = $this->addTermQuery($nested, 'status.state', 'active');
                        $nested = $this->addLocaleQuery($nested, $localeFilter);
                        return $this->addNestedQuery($query, $nested);
                    default:
                        throw new BadRequestException(['Status operator not supported.']);
                        break;
                }
            }
        }
    }

    protected function addLocaleQuery($query, $localeFilter)
    {
        if (!empty($localeFilter)) {
            $query = $this->addTermQuery($query, 'status.locale', $localeFilter->code);
        }

        return $query;
    }

    protected function decorateResult($result, $params, $total = 0)
    {
        if (!is_array($result)) {
            $result = new Model($result);
            $result->setParams($params);
            $meta = [
                'id' => $params[0],
                'include' => (isset($params[1]))?$params[1]:[],
                'locale' => (isset($params[2]))?$params[2]:null,
                'status' => (isset($params[3]))?$params[3]:null
            ];
            $result->setMeta($meta);
            $result->load((isset($params[1]))?$params[1]:[]);

            return $result;
        }

        $count = count($result);
        $result = new Collection($result);
        $result->setParams($params);

        $offset = (isset($params[1]))?$params[1]:0;
        $limit = (isset($params[2]))?$params[2]:10000 - $offset;
        $meta = [
            'count' => $count,
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total,
            'filter' => (isset($params[0]))?$params[0]:[],
            'sort' => (isset($params[3]))?$params[3]:[],
            'include' => (isset($params[4]))?$params[4]:[],
            'locale' => (isset($params[5]))?$params[5]:null,
            'status' => (isset($params[6]))?$params[6]:null
        ];
        $result->setMeta($meta);
        $result->load((isset($params[4]))?$params[4]:[]);

        return $result;
    }

    protected function parseLocale($locale)
    {
        $localeCodes = [null];
        if (! is_null($locale)) {
            $locale = (MetaData::getLocaleById($locale))?MetaData::getLocaleById($locale):MetaData::getLocaleByCode($locale);

            $localeCodes[] = $locale->code;
        } else {
            foreach (MetaData::getLocales() as $l) {
                $localeCodes[] = $l->code;
            }
        }

        return array($locale, $localeCodes);
    }



    // public function onEntityTypeCreated($command, $result)
    // {

    //     $params = [
    //         'index' => $result->endpoint,
    //         'body' => [
    //             'settings' => [
    //                 "index.mapping.single_type" => true
    //             ],
    //             'mappings' => [
    //                 "doc" => new stdClass()
    //             ]
    //         ]
    //     ];

    //     $this->client->indices()->create($params);
    // }

    // public function onBeforeEntityTypeUpdated($command)
    // {
    //     self::$memory['entity-type'][$command->id] = $this->entityTypeRepository->fetch($command->id);
    // }

    // public function onEntityTypeUpdated($command, $result)
    // {
    //     if(!isset(self::$memory['entity-type'][$command->id]))
    //     {
    //         return;
    //     }

    //     $oldType = self::$memory['entity-type'][$command->id];

    //     if($oldType->endpoint == $result->endpoint)
    //     {
    //         return;
    //     }

    //     $params = [
    //         'index' => $oldType->endpoint
    //     ];

    //     if(!$this->client->indices()->exists($params))
    //     {
    //         return;
    //     }

    //     $params = [
    //         'index' => $result->endpoint,
    //         'body' => [
    //             'settings' => [
    //                 "index.mapping.single_type" => true
    //             ],
    //             'mappings' => [
    //                 "doc" => new stdClass()
    //             ]
    //         ]
    //     ];

    //     $this->client->indices()->create($params);

    //     $params = array(
    //         'body' => array(
    //             'source' => array(
    //                 'index'  => $oldType->endpoint
    //             ),
    //             'dest' => array(
    //                 'index' => $result->endpoint
    //             )
    //         )
    //     );
    // }

    // public function onBeforeEntityTypeDeleted($command)
    // {
    //     self::$memory['entity-type'][$command->id] = $this->entityTypeRepository->fetch($command->id);
    // }

    // public function onEntityTypeDeleted($command, $result)
    // {
    //     if(!isset(self::$memory['entity-type'][$command->id]))
    //     {
    //         return;
    //     }
    //     $oldType = self::$memory['entity-type'][$command->id];

    //     $this->deleteIndex($oldType->endpoint);
    // }
}
