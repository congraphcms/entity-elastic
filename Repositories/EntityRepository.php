<?php
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Repositories;

use Carbon\Carbon;
use Cookbook\Contracts\Eav\EntityRepositoryContract;
use Cookbook\Contracts\Eav\FieldHandlerFactoryContract;
use Cookbook\Core\Exceptions\Exception;
use Cookbook\Core\Exceptions\NotFoundException;
use Cookbook\Core\Exceptions\BadRequestException;
use Cookbook\Core\Facades\Trunk;
// use Cookbook\Core\Repositories\AbstractRepository;
use Cookbook\Core\Repositories\Collection;
use Cookbook\Core\Repositories\Model;
use Cookbook\Core\Repositories\UsesCache;
use Cookbook\Eav\Managers\AttributeManager;

use Cookbook\EntityElastic\Traits\ElasticQueryBuilderTrait;

use Cookbook\Eav\Facades\MetaData;


use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Config;
use stdClass;

/**
 * EntityElasticRepository class
 *
 * Repository for entity elastic database queries
 *
 * @uses        Illuminate\Database\Connection
 * @uses        Cookbook\Core\Repository\AbstractRepository
 * @uses        Cookbook\Contracts\Eav\AttributeHandlerFactoryContract
 * @uses        Cookbook\Eav\Managers\AttributeManager
 *
 * @author      Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright   Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package     cookbook/entity-elastic
 * @since       0.1.0-alpha
 * @version     0.1.0-alpha
 */
class EntityRepository implements EntityRepositoryContract//, UsesCache
{

    use ElasticQueryBuilderTrait;

    /**
     * Factory for field handlers,
     * makes appropriate field handler depending on attribute data type
     *
     * @var \Cookbook\Contracts\Eav\FieldHandlerFactoryContract
     */
    protected $fieldHandlerFactory;


    /**
     * Helper for attributes
     *
     * @var \Cookbook\Eav\Managers\AttributeManager
     */
    protected $attributeManager;

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
     * @param Cookbook\Eav\Handlers\AttributeHandlerFactoryContract $attributeHandlerFactory
     * @param Cookbook\Eav\Managers\AttributeManager $attributeManager
     *
     * @return void
     */
    public function __construct(
        ClientBuilder $elasticClientBuilder,
        FieldHandlerFactoryContract $fieldHandlerFactory,
        AttributeManager $attributeManager
    )
    {

        // Inject dependencies
        $this->fieldHandlerFactory = $fieldHandlerFactory;
        $this->attributeManager = $attributeManager;

        
        $hosts = Config::get('cb.elastic.hosts');
        $prefix = Config::get('cb.elastic.index_prefix');
        $this->indexName = $prefix . 'entities';

        $this->client = $elasticClientBuilder->create()
                                            ->setHosts($hosts)
                                            ->build();
        $indexParams = [
            'index' => $this->indexName
        ];

        if(!$this->client->indices()->exists($indexParams))
        {
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
        $this->update($command->id, $command->params, $result);   
    }

    public function onEntityDeleted($command, $result)
    {
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
    public function create($model, $result = null)
    {
        $params = [];
        $params['index'] = $this->indexName;
        $params['type'] = 'doc';

        $body = [];

        if(!($result instanceof Model) || !is_integer($result->id) || empty($result->id))
        {
            $body['created_at'] = date("Y-m-d H:i:s");
            $body['updated_at'] = date("Y-m-d H:i:s");
        }
        else
        {
            $params['id'] = $result->id;
            $body['id'] = $params['id'];
            $body['created_at'] = $result->created_at->tz('UTC')->toDateTimeString();
            $body['updated_at'] = $result->updated_at->tz('UTC')->toDateTimeString();
        }
        
        $body['fields'] = [];
        $body['status'] = [];
        $body['entity_type_id'] = $model['entity_type_id'];
        $body['attribute_set_id'] = $model['attribute_set_id'];

        $fields = array();
        if (! empty($model['fields']) && is_array($model['fields']))
        {
            $fields = $model['fields'];
        }

        $locale = false;
        $localeCodes = [null];
        $locale_id = null;

        if (! empty($model['locale']))
        {
            list($locale, $localeCodes) = $this->parseLocale($model['locale']);
            $locale_id = $locale->id;
        }

        $status = null;
        if (! empty($model['status']))
        {
            $status = $model['status'];
        }

        $attributeSet = MetaData::getAttributeSetById($body['attribute_set_id']);

        foreach ($attributeSet['attributes'] as $setAttribute)
        {
            $attribute = MetaData::getAttributeById($setAttribute->id);
            $code = $attribute->code;
            $fieldHandler = $this->fieldHandlerFactory->make($attribute->field_type);
            
            if(!$attribute->localized)
            {
                if(array_key_exists($code, $fields))
                {
                    $value = $fields[$code];
                }
                else
                {
                    $value = $attribute->default_value;
                }

                $value = $fieldHandler->prepareForElastic($value, $attribute);
                $body['fields'][$code] = $value;
                continue;
            }

            foreach (MetaData::getLocales() as $l)
            {
                if($locale)
                {
                    if($locale->id == $l->id)
                    {
                        $value = (array_key_exists($code, $fields))?$fields[$code]:$attribute->default_value;
                        $value = $fieldHandler->prepareForElastic($value, $attribute);
                        $body['fields'][$code . '__' . $l->code] = $value;
                        continue;
                    }
                    
                    $value = $attribute->default_value;
                    $value = $fieldHandler->prepareForElastic($value, $attribute);
                    $body['fields'][$code . '__' . $l->code] = $value;
                    continue;
                }

                if (array_key_exists($code, $fields) && array_key_exists($l->code, $fields[$code]))
                {
                    $value = $fields[$code][$l->code];
                    $value = $fieldHandler->prepareForElastic($value, $attribute);
                    $body['fields'][$code . '__' . $l->code] = $value;
                    continue;
                }

                $value = $attribute->default_value;
                $value = $fieldHandler->prepareForElastic($value, $attribute);
                $body['fields'][$code . '__' . $l->code] = $value;
            }
        }

        $entityType = MetaData::getEntityTypeById($model['entity_type_id']);
        $body['localized'] = !!$entityType->localized;
        $body['localized_workflow'] = !!$entityType->localized_workflow;
        

        if (isset($status))
        {
            if(is_array($status))
            {
                $point = [];
                foreach ($status as $loc => $value)
                {
                    $point[$loc] = MetaData::getWorkflowPointByStatus($value);
                }
            }
            else
            {
                $point = MetaData::getWorkflowPointByStatus($status);
            }
        }
        else
        {
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

        foreach ($localeCodes as $lc)
        {
            if(is_array($point))
            {
                $s = $point[$lc]->status;
            }
            else
            {
                $s = $point->status;
            }
            $statusObj = [
                'status' => $s,
                'locale' => $lc,
                'state' => 'active',
                'scheduled_at' => null,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ];

            $body['status'][] = $statusObj;
        }

        $params['body'] = $body;

        $response = $this->client->index($params);
        // var_dump($response);

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
        $params['type'] = 'doc';
        $params['id'] = $id;

        // fetch raw document from database
        $rawDocument = $this->fetchRaw($id);

        $body = $rawDocument['_source'];

        $changed = false;

        $fields = array();
        if (! empty($model['fields']) && is_array($model['fields']))
        {
            $fields = $model['fields'];
        }

        $locale = false;
        $localeCodes = [null];
        $locale_id = null;

        if (! empty($model['locale']))
        {
            list($locale, $localeCodes) = $this->parseLocale($model['locale']);
            $locale_id = $locale->id;
        }

        $status = null;
        if (! empty($model['status']))
        {
            $status = $model['status'];
        }

        // update fields
        $attributeSet = MetaData::getAttributeSetById($body['attribute_set_id']);
        foreach ($attributeSet['attributes'] as $setAttribute)
        {
            $attribute = MetaData::getAttributeById($setAttribute->id);
            $code = $attribute->code;
            $fieldHandler = $this->fieldHandlerFactory->make($attribute->field_type);

            if(!array_key_exists($code, $fields))
            {
                continue;
            }
            
            if(!$attribute->localized || $locale)
            {
                $fieldName = $code;
                if($attribute->localized)
                {
                    $fieldName .= '__' . $locale->code;
                }

                $value = $fieldHandler->prepareForElastic($fields[$code], $attribute);
                if($value === $body['fields'][$fieldName])
                {
                    continue;
                }

                $body['fields'][$fieldName] = $value;
                $changed = true;
                continue;
            }

            foreach (MetaData::getLocales() as $l)
            {
                $fieldName = $code . '__' . $l->code;

                if(!array_key_exists($l->code, $fields[$code]))
                {
                    continue;
                }

                $value = $fieldHandler->prepareForElastic($fields[$code][$l->code], $attribute);
                if($value === $body['fields'][$fieldName])
                {
                    continue;
                }

                $body['fields'][$fieldName] = $fields[$code][$l->code];
                $changed = true;
            }
        }

        $entityType = MetaData::getEntityTypeById($body['entity_type_id']);
        
        // update status
        if (isset($status))
        {
            if(is_array($status))
            {
                $point = [];
                foreach ($status as $loc => $value)
                {
                    $point[$loc] = MetaData::getWorkflowPointByStatus($value);
                }
            }
            else
            {
                $point = MetaData::getWorkflowPointByStatus($status);
            }

            $localeCodes = [];
            if (! $entityType->localized_workflow) {
                $localeCodes[] = null;
            } else {
                if (empty($locale)) {
                    if(is_array($status))
                    {
                        foreach ($status as $loc => $value)
                        {
                            $localeCodes[] = $loc;
                        }
                    }
                    else
                    {
                        foreach (MetaData::getLocales() as $l)
                        {
                            $localeCodes[] = $l->code;
                        }
                    }
                } else {
                    $localeCodes[] = $locale->code;
                }
            }

            foreach ($localeCodes as $lc)
            {
                if(is_array($point))
                {
                    $s = $point[$lc]->status;
                }
                else
                {
                    $s = $point->status;
                }

                $statusChanged = false;

                foreach ($body['status'] as &$oldStatus)
                {
                    if($oldStatus['state'] == 'active' 
                        && $oldStatus['locale'] = $lc 
                        && $oldStatus['status'] !== $s)
                    {
                        $oldStatus['state'] = 'history';
                        $statusChanged = true;
                        $changed = true;
                        break;
                    }
                }

                if(!$statusChanged)
                {
                    continue;
                }

                $statusObj = [
                    'status' => $s,
                    'locale' => $lc,
                    'state' => 'active',
                    'scheduled_at' => null,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ];

                $body['status'][] = $statusObj;
            }
        }

        

        if($changed)
        {
            // update updated_at
            if(!($result instanceof Model) || !is_integer($result->id) || empty($result->id))
            {
                $body['updated_at'] = date("Y-m-d H:i:s");
            }
            else
            {
                $body['updated_at'] = $result->updated_at->tz('UTC')->toDateTimeString();
            }

            // update data in elastic
            $params['body'] = [];
            $params['body']['doc'] = $body;

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
        $params['type'] = 'doc';
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
        $params['type'] = 'doc';
        $params['id'] = $id;

        $changed = false;

        // get the locale
        list($locale, $localeCodes) = $this->parseLocale($locale_id);

        // get the raw entity
        $rawDocument = $this->fetchRaw($id);
        $entity = $this->fetch($id, [], $locale_id);
        $body = $rawDocument['_source'];

        // update status
        foreach ($body['status'] as &$status)
        {
            if($status['locale'] == $locale->code && $status['state'] == 'active')
            {
                $status['state'] = 'history';
                $changed = true;
            }
        }

        // find fields
        $sufix = '__' . $locale->code;
        $sufixLength = strlen($sufix);
        $fieldsForDelete = [];
        foreach ($body['fields'] as $key => $value)
        {
            if(substr($key, -$sufixLength) === $sufix)
            {
                $fieldsForDelete[] = $key;
            }
        }

        // remove fields
        foreach ($fieldsForDelete as $key)
        {
            unset($body['fields'][$key]);
            $changed = true;
        }

        if($changed)
        {
            // update updated_at
            $body['updated_at'] = date("Y-m-d H:i:s");
            // update data in elastic
            $params['body'] = [];
            $params['body']['doc'] = $body;

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
        if($attribute->localized)
        {
            foreach (MetaData::getLocales() as $locale)
            {
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
        }
        else
        {
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
        if (Trunk::has($params, 'entity'))
        {
            $result = Trunk::get($params, 'entity');
            $result->clearIncluded();
            $result->load($include);
            $meta = ['id' => $id, 'include' => $include, 'locale' => $locale, 'status' => $status];
            $result->setMeta($meta);
            return $result;
        }

        list($locale, $localeCodes) = $this->parseLocale($locale);

        $result = $this->fetchRaw($id);

        $result = $this->formatEntity($result, $status, $locale, $localeCodes);

        $result = $this->decorateResult($result, $params);

        return $result;
    }

    public function fetchRaw($id)
    {

        $query = [
            'index' => $this->indexName,
            'type' => 'doc',
            'id' => $id
        ];

        try
        {
            $result = $this->client->get($query);
        }
        catch(\Elasticsearch\Common\Exceptions\Missing404Exception $e)
        {
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
        if (Trunk::has($params, 'entity'))
        {
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
        $query = $this->querySorting($query, $sort, $locale, $localeCodes);
        $query = $this->queryFiltering($query, $filter, $locale, $localeCodes);

        // var_dump($query);

        $result = $this->client->search($query);

        $total = $result['hits']['total'];

        $result = $this->formatEntities($result, $status, $locale, $localeCodes);

        $result = $this->decorateResult($result, $params, $total);

        return $result;
    }

    protected function queryFiltering($query, $filters, $localeFilter, $localeCodes)
    {
        $fieldFilters = [];
        $status = null;
        $public = false;
        $fulltextSearch = null;
        


        foreach ($filters as $key => $filter) {

            if ($key == 's')
            {
                $fulltextSearch = strval($filter);
                continue;
            }


            if (substr($key, 0, 7) === "fields.")
            {
                $code = substr($key, 7);
                $fieldFilters[$code] = $filter;
                continue;
            }
            
            if(!is_array($filter))
            {
                $query = $this->addTermQuery($query, $key, $filter);
                continue;
            } 

            $query = $this->parseFilterOperator($query, $key, $filter);
        }
        if (! empty($fieldFilters))
        {
            // $nested = $this->createNestedQuery('fields');

            foreach ($fieldFilters as $code => $filter)
            {
                $attribute = MetaData::getAttributeByCode($code);
                $query = $this->parseFieldFilter($query, $filter, $attribute, $localeFilter, $localeCodes);
            }

            // $query = $this->addNestedQuery($query, $nested);
        }

        if( ! empty($fulltextSearch) )
        {
            $query = $this->parseFulltextSearch($query, $fulltextSearch, $localeFilter);
        }

        return $query;
    }

    protected function parseFulltextSearch($query, $fulltextSearch, $localeFilter)
    {
        $attributes = MetaData::getAttributes();
        $fields = [];
        foreach ($attributes as $attribute)
        {
            if(in_array($attribute->field_type, ['text', 'tags']))
            {
                if(!$attribute->localized)
                {
                    $fields[] = 'fields.' . $attribute->code;
                    continue;
                }

                if($localeFilter)
                {
                    $fields[] = 'fields.' . $attribute->code . '__' . $localeFilter->code;
                    continue;
                }


                $fields[] = 'fields.' . $attribute->code . '__*';
            }
        }
        return $this->addMultiMatchQuery($query, $fields, $fulltextSearch, 'cross_fields');
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

                if (substr($sortCriteria, 0, 7) === "fields.")
                {
                    $code = substr($sortCriteria, 7);
                    $attribute = MetaData::getAttributeByCode($code);
                    if(in_array($attribute->field_type, ['text', 'tags']))
                    {
                        if(!$attribute->localized)
                        {
                            $sortCriteria = $sortCriteria . '.keyword';
                        }
                        else
                        {
                            if($localeFilter)
                            {
                                $sortCriteria = $sortCriteria . '__' . $localeFilter->code . '.keyword';
                            }
                            else
                            {
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
        if(empty($statusFilter))
        {
            if(!empty($localeFilter))
            {
                $nested = $this->addTermQuery($nested, 'status.locale', $localeFilter->code);
                return $this->addNestedQuery($query, $nested);
            }

            return $query;
        }
        
        if(!is_array($statusFilter))
        {
            $nested = $this->addTermQuery($nested, 'status.status', $statusFilter);
            return $this->addNestedQuery($query, $nested);
        } 
        else 
        {
            foreach ($statusFilter as $operator => $value) {
                switch (true) {
                    case ($operator === 'e'):
                        $nested = $this->addTermQuery($nested, 'status.status', $value);
                        $nested = $this->addLocaleQuery($nested, $localeFilter);
                        return $this->addNestedQuery($query, $nested);
                    case ($operator === 'ne'):
                        $nested = $this->addNotTermQuery($nested, 'status.status', $value);
                        $nested = $this->addLocaleQuery($nested, $localeFilter);
                        return $this->addNestedQuery($query, $nested);
                    case ($operator === 'in'):
                        $nested = $this->addTermsQuery($nested, 'status.status', $value);
                        $nested = $this->addLocaleQuery($nested, $localeFilter);
                        return $this->addNestedQuery($query, $nested);
                    case ($operator === 'nin'):
                        $nested = $this->addNotTermsQuery($nested, 'status.status', $value);
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
        if(!empty($localeFilter))
        {
            $query = $this->addTermQuery($query, 'status.locale', $localeFilter->code);
        }

        return $query;
    }

    protected function decorateResult($result, $params, $total = 0)
    {
        if(!is_array($result))
        {
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
        if (! is_null($locale)) 
        {
            $locale = (MetaData::getLocaleById($locale))?MetaData::getLocaleById($locale):MetaData::getLocaleByCode($locale);

            $localeCodes[] = $locale->code;
        } 
        else 
        {
            foreach (MetaData::getLocales() as $l)
            {
                $localeCodes[] = $l->code;
            }
        }

        return array($locale, $localeCodes);
    }

    protected function formatEntities($result, $status, $locale, $localeCodes)
    {
        $entities = [];


        foreach ($result['hits']['hits'] as $entity) {
            $entity = $this->formatEntity($entity, $status, $locale, $localeCodes);
            $entities[] = $entity;
        }

        return $entities;
    }

    protected function formatEntity($result, $status, $locale, $localeCodes)
    {
        $result['_source']['status'] = $this->getValidStatuses($result, $status, $localeCodes);

        

        $entity = new stdClass();
        $source = $result['_source'];
        $fields = $source['fields'];
        $entity->id = (is_numeric($result['_id']))?intval($result['_id']):$result['_id'];
        // $entity->version = $result['_version'];
        $entity->type = 'entity';
        if (! is_null($locale) && $result['_source']['localized']) {
            $result['_source']['locale'] = $locale->code;
            $entity->locale = $locale->code;
        }
        $entity->entity_type_id = $source['entity_type_id'];
        $entity->attribute_set_id = $source['attribute_set_id'];

        $type = MetaData::getEntityTypeById($entity->entity_type_id);
        $entity->entity_type = $type->code;
        $entity->entity_endpoint = $type->endpoint;
        $entity->workflow_id = $type->workflow_id;

        $attributeSet = MetaData::getAttributeSetById($entity->attribute_set_id);
        $entity->attribute_set_code = $attributeSet->code;
        $entity->primary_field = MetaData::getAttributeById($attributeSet->primary_attribute_id)->code;

        $entity->localized = intval($source['localized']);
        $entity->localized_workflow = intval($source['localized_workflow']);

        $timezone = (Config::get('app.timezone'))?Config::get('app.timezone'):'UTC';

        $entity->created_at = Carbon::parse($source['created_at'])->tz($timezone);
        $entity->updated_at = Carbon::parse($source['updated_at'])->tz($timezone);

        $entity->status = $this->formatStatus($source['status'], $locale, $localeCodes);

        $entity->fields = $this->formatFields($source, $locale, $localeCodes);

        return $entity;
    }

    protected function formatStatus($statuses, $locale, $localeCodes)
    {
        $parsed = null;

        if(empty($locale))
        {
            $parsed = [];
        }

        foreach ($statuses as $status)
        {
            if(!in_array($status['locale'], $localeCodes))
            {
                continue;
            }

            if(!empty($locale) || $status['locale'] === null)
            {
                $parsed = $status['status'];
                continue;
            }

            $parsed[$status['locale']] = $status['status'];
        }

        return $parsed;
    }

    protected function formatFields($source, $locale, $localeCodes)
    {
        $entityType = MetaData::getEntityTypeById($source['entity_type_id']);
        $attributeSet = MetaData::getAttributeSetById($source['attribute_set_id']);

        $attributeSettings = $this->attributeManager->getFieldTypes();
        $fieldHandlers = [];
        $fields = new stdClass();

        if(!$entityType || !$attributeSet)
        {
            return false;
        }

        foreach ($attributeSet->attributes as $attr)
        {
            $attribute = MetaData::getAttributeById($attr->id);
            $settings = $attributeSettings[$attribute->field_type];
            $code = $attribute->code;

            if(!$attribute->localized)
            {
                $value = (isset($source['fields'][$code])) ? 
                            $source['fields'][$code] :
                            null;
                $formattedValue = $this->formatValue($value, $attribute, $settings);
                $fields->$code = $formattedValue;
                continue;
            }

            if($locale)
            {
                $value = (isset($source['fields'][$code . '__' . $locale->code])) ? 
                            $source['fields'][$code . '__' . $locale->code] :
                            null;
                $formattedValue = $this->formatValue($value, $attribute, $settings);
                $fields->$code = $formattedValue;
                continue;
            }

            $fields->$code = new stdClass();
            foreach (MetaData::getLocales() as $l)
            {
                $localeCode = $l->code;
                if(!in_array($localeCode, $localeCodes))
                {
                    continue;
                }

                $value = (isset($source['fields'][$code . '__' . $localeCode])) ? 
                            $source['fields'][$code . '__' . $localeCode] :
                            null;
                $fields->$code->$localeCode = $this->formatValue($value, $attribute, $settings);
            }
        }

        return $fields;
    }

    protected function formatValue($value, $attribute, $settings)
    {
        $handlerName = $settings['handler'];
        $hasMultipleValues = $settings['has_multiple_values'];
        $fieldHandler = $this->fieldHandlerFactory->make($attribute->field_type);

        if($hasMultipleValues)
        {
            $formattedValue = [];
            if($value == null)
            {
                return $formattedValue;
            }
            foreach ($value as $valueItem)
            {
                $formattedValue[] = $fieldHandler->formatValue($valueItem, $attribute);
            }

            return $formattedValue;
        }

        $formattedValue = $fieldHandler->formatValue($value, $attribute);

        return $formattedValue;
    }



    protected function getValidStatuses($entity, $statusFilter, &$localeCodes)
    {
        $validStatuses = [];
        foreach ($entity['_source']['status'] as $status)
        {
            if(!in_array($status['locale'], $localeCodes))
            {
                continue;
            }

            if($status['state'] !== 'active')
            {
                continue;
            }

            if(empty($statusFilter))
            {
                $validStatuses[] = $status;
                continue;
            }

            if(!is_array($statusFilter) )
            {
                if($status['status'] == $statusFilter) {
                    $validStatuses[] = $status;
                }

                continue;
            }

            foreach ($statusFilter as $operator => $value) {
                switch ($operator) {
                    case 'e':
                        if($status['status'] == $value) {
                            $validStatuses[] = $status;
                        }
                        break;
                    case 'ne':
                        if($status['status'] != $value) {
                            $validStatuses[] = $status;
                        }
                        break;
                    case 'in':
                        if(in_array($status['status'], $value)) {
                            $validStatuses[] = $status;
                        }
                        break;
                    case 'nin':
                        if(!in_array($status['status'], $value)) {
                            $validStatuses[] = $status;
                        }
                        break;
                    
                    default:
                        throw new BadRequestException(['Status operator not supported.']);
                        break;
                }
            }
        }

        if(empty($validStatuses))
        {
            throw new NotFoundException(['Entity not found.']);
        }
        if($entity['_source']['localized_workflow'])
        {
            $availableLocaleCodes = [];
            foreach ($validStatuses as $status)
            {
                foreach ($localeCodes as $code)
                {
                    if($status['locale'] == $code)
                    {
                        $availableLocaleCodes[] = $code;
                    }
                }
            }

            $localeCodes = $availableLocaleCodes;
        }
        

        return $validStatuses;
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