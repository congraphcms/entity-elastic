<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Services;

use Congraph\Contracts\Eav\FieldHandlerFactoryContract;
use Congraph\Core\Exceptions\Exception;
use Congraph\Core\Exceptions\NotFoundException;
use Congraph\Core\Exceptions\BadRequestException;
use Congraph\Core\Repositories\Collection;
use Congraph\Core\Repositories\Model;
use Congraph\Eav\Managers\AttributeManager;
use Congraph\Eav\Facades\MetaData;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use stdClass;

/**
 * EntityFormater class
 *
 * Service for entity formating
 *
 * @uses        Congraph\Contracts\Eav\AttributeHandlerFactoryContract
 * @uses        Congraph\Eav\Managers\AttributeManager
 *
 * @author      Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright   Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package     congraph/entity-elastic
 * @since       0.1.0-alpha
 * @version     0.1.0-alpha
 */
class EntityFormater
{

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
     * Create new EntityFormater
     *
     * @param Congraph\Eav\Handlers\AttributeHandlerFactoryContract $attributeHandlerFactory
     * @param Congraph\Eav\Managers\AttributeManager $attributeManager
     *
     * @return void
     */
    public function __construct(
        FieldHandlerFactoryContract $fieldHandlerFactory,
        AttributeManager $attributeManager
    ) {

        // Inject dependencies
        $this->fieldHandlerFactory = $fieldHandlerFactory;
        $this->attributeManager = $attributeManager;
    }

    /**
     * Prepare model as body of ES document
     * 
     * @param  Model|Array $model
     * @return Array document body
     */
    public function prepareEntityModel($model): Array {
        // body of ES document
        $body = [];

        // fields placeholder
        $fields = [];

        // if model is from EAV Repository (has ID and rest of generated fields)
        if ($model instanceof Model) {
            $body['id'] = $model->id;
            $body['created_at'] = 
                $result->created_at->tz('UTC')->getTimestamp();
            $body['updated_at'] = 
                $result->updated_at->tz('UTC')->getTimestamp();
            $body['entity_type_id'] = $result->entity_type_id;
            $body['attribute_set_id'] = $result->attribute_set_id;
            $fields = $result->toArray()['fields'];
        
        // generate mandatory fields
        } else {
            $body['created_at'] = gmdate("U");
            $body['updated_at'] = gmdate("U");
            $body['entity_type_id'] = $model['entity_type_id'];
            $body['attribute_set_id'] = $model['attribute_set_id'];
            if (! empty($model['fields']) && is_array($model['fields'])) {
                $fields = $model['fields'];
            }
        }

        


    }


    public function formatEntities($result, $status, $locale, $localeCodes)
    {
        $entities = [];


        foreach ($result['hits']['hits'] as $rawEntity) {
            try {
                $entity = $this->formatEntity($rawEntity, $status, $locale, $localeCodes, false, false, true);
            } catch (NotFoundException $e) {
                continue;
            }
            
            $entities[] = $entity;
        }

        return $entities;
    }

    public function formatEntity($result, $status, $locale, $localeCodes, $nested = false, $source = false, $multiple = false)
    {
        $entity = new stdClass();
        if (!$source) {
            $id = $result['_id'];
            $result = $result['_source'];
            $result['id'] = $id;
        }

        $result['status'] = $this->getValidStatuses($result, $status, $localeCodes, !$multiple);
        $fields = $result['fields'];
        $entity->id = (is_numeric($result['id']))?intval($result['id']):$result['id'];
        // $entity->version = $result['_version'];
        $entity->type = 'entity';
        if (! is_null($locale) && $result['localized']) {
            $result['locale'] = $locale->code;
            $entity->locale = $locale->code;
        }
        $entity->entity_type_id = $result['entity_type_id'];
        $entity->attribute_set_id = $result['attribute_set_id'];

        $type = MetaData::getEntityTypeById($entity->entity_type_id);
        $entity->entity_type = $type->code;
        $entity->entity_endpoint = $type->endpoint;
        $entity->workflow_id = $type->workflow_id;

        $attributeSet = MetaData::getAttributeSetById($entity->attribute_set_id);
        $entity->attribute_set_code = $attributeSet->code;
        $entity->primary_field = MetaData::getAttributeById($attributeSet->primary_attribute_id)->code;

        $entity->localized = intval($result['localized']);
        $entity->localized_workflow = intval($result['localized_workflow']);

        $timezone = (Config::get('app.timezone'))?Config::get('app.timezone'):'UTC';

        $entity->created_at = Carbon::createFromTimestamp($result['created_at'])->tz($timezone);
        $entity->updated_at = Carbon::createFromTimestamp($result['updated_at'])->tz($timezone);

        $entity->status = $this->formatStatus($result['status'], $locale, $localeCodes);

        $entity->fields = $this->formatFields($result, $status, $locale, $localeCodes, $nested);

        return $entity;
    }

    protected function formatStatus($statuses, $locale, $localeCodes)
    {
        $parsed = null;

        if (empty($locale)) {
            $parsed = [];
        }

        foreach ($statuses as $status) {
            if (!in_array($status['locale'], $localeCodes)) {
                continue;
            }

            if (!empty($locale) || $status['locale'] === null) {
                $parsed = $status['status'];
                continue;
            }

            $parsed[$status['locale']] = $status['status'];
        }

        return $parsed;
    }

    protected function getValidStatuses($entity, $statusFilter, &$localeCodes, $updateLocaleCodes = true)
    {
        $validStatuses = [];
        foreach ($entity['status'] as $status) {
            if (!in_array($status['locale'], $localeCodes)) {
                continue;
            }

            if ($status['state'] !== 'active') {
                continue;
            }

            if (empty($statusFilter)) {
                $validStatuses[] = $status;
                continue;
            }

            if (!is_array($statusFilter)) {
                if ($status['status'] == $statusFilter) {
                    $validStatuses[] = $status;
                }

                continue;
            }

            foreach ($statusFilter as $operator => $value) {
                switch ($operator) {
                    case 'e':
                        if ($status['status'] == $value) {
                            $validStatuses[] = $status;
                        }
                        break;
                    case 'ne':
                        if ($status['status'] != $value) {
                            $validStatuses[] = $status;
                        }
                        break;
                    case 'in':
                        if (in_array($status['status'], $value)) {
                            $validStatuses[] = $status;
                        }
                        break;
                    case 'nin':
                        if (!in_array($status['status'], $value)) {
                            $validStatuses[] = $status;
                        }
                        break;
                    
                    default:
                        throw new BadRequestException(['Status operator not supported.']);
                        break;
                }
            }
        }

        if (empty($validStatuses)) {
            throw new NotFoundException(['Entity not found.']);
        }
        if ($entity['localized_workflow']) {
            $availableLocaleCodes = [null];
            foreach ($validStatuses as $status) {
                foreach ($localeCodes as $code) {
                    if ($status['locale'] == $code) {
                        $availableLocaleCodes[] = $code;
                    }
                }
            }

            if ($updateLocaleCodes) {
                $localeCodes = $availableLocaleCodes;
            }
        }
        

        return $validStatuses;
    }

    protected function formatFields($source, $status, $locale, $localeCodes, $nested = false)
    {
        $entityType = MetaData::getEntityTypeById($source['entity_type_id']);
        $attributeSet = MetaData::getAttributeSetById($source['attribute_set_id']);

        $attributeSettings = $this->attributeManager->getFieldTypes();
        $fieldHandlers = [];
        $fields = new stdClass();

        if (!$entityType || !$attributeSet) {
            return false;
        }

        foreach ($attributeSet->attributes as $attr) {
            $attribute = MetaData::getAttributeById($attr->id);
            $settings = $attributeSettings[$attribute->field_type];
            $code = $attribute->code;

            if (!$attribute->localized) {
                $value = (isset($source['fields'][$code])) ?
                            $source['fields'][$code] :
                            null;
                $formattedValue = $this->formatValue($value, $attribute, $status, $locale, $localeCodes, $settings, $nested);
                $fields->$code = $formattedValue;
                continue;
            }

            if ($locale) {
                $value = (isset($source['fields'][$code . '__' . $locale->code])) ?
                            $source['fields'][$code . '__' . $locale->code] :
                            null;
                $formattedValue = $this->formatValue($value, $attribute, $status, $locale, $localeCodes, $settings, $nested);
                $fields->$code = $formattedValue;
                continue;
            }

            $fields->$code = new stdClass();
            $lcls = MetaData::getLocales();
            for ($i = 0; $i < count($lcls); $i++) {
                $l = $lcls[$i];
                $lc = $l->code;
                if (!in_array($lc, $localeCodes)) {
                    continue;
                }

                $value = (isset($source['fields'][$code . '__' . $lc])) ?
                    $source['fields'][$code . '__' . $lc] :
                    null;

                $fields->$code->$lc = $this->formatValue($value, $attribute, $status, $locale, $localeCodes, $settings, $nested);
            }
        }

        return $fields;
    }

    protected function formatValue($value, $attribute, $status, $locale, $localeCodes, $settings, $nested = false)
    {
        $handlerName = $settings['elastic_handler'];
        $hasMultipleValues = $settings['has_multiple_values'];
        $fieldHandler = $this->fieldHandlerFactory->make($attribute->field_type);

        if ($hasMultipleValues) {
            $formattedValue = [];
            if ($value == null) {
                return $formattedValue;
            }
            foreach ($value as $index => $valueItem) {
                $formattedValue[] = $fieldHandler->formatValue($valueItem, $attribute, $status, $locale, $localeCodes, $nested);
            }

            return $formattedValue;
        }

        $formattedValue = $fieldHandler->formatValue($value, $attribute, $status, $locale, $localeCodes, $nested);

        return $formattedValue;
    }
}
