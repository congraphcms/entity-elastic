<?php 
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Fields\Asset;

use Cookbook\EntityElastic\Fields\AbstractFieldHandler;
use Cookbook\Eav\Facades\MetaData;
use Cookbook\Core\Facades\Trunk;
use stdClass;

/**
 * AssetFieldHandler class
 * 
 * Responsible for handling asset field types
 * 
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	cookbook/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class AssetFieldHandler extends AbstractFieldHandler {

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
		if(empty($value))
		{
			return null;
		}
		$value = $value['id'];
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
		if(empty($value))
		{
			return null;
		}
		$relation = new stdClass();
		$relation->id = $value;
		$relation->type = 'file';
		return $relation;
	}

	/**
	 * Handle File Delete
	 * 
	 * @return void
	 */
	public function onFileDelete($command, $result)
	{
		$query = $this->createEmptyQuery($this->indexName);
		$attributes = MetaData::getAttributes();
		$fieldKeys = [];
		foreach ($attributes as $attribute)
		{
			$attributeSettings = $this->attributeManager->getFieldTypes()[$attribute->field_type];
			if(get_class($this) != $attributeSettings['elastic_handler'])
			{
				continue;
			}

			if(!$attribute->localized)
			{
				$fieldKeys[] = $attribute->code;
				continue;
			}

			foreach (MetaData::getLocales() as $locale)
			{
				$fieldKeys[] = $attribute->code . '__' . $locale->code;
			}
		}

		foreach ($fieldKeys as $fieldKey)
		{
			$query = $this->addShouldTermQuery($query, 'fields.' . $fieldKey, $command->id);
		}

		$rawDocuments = $this->client->search($query);

		$changed = false;

		foreach ($rawDocuments['hits']['hits'] as $document)
		{
			$id = $document['_id'];
			$body = $document['_source'];

			foreach ($body['fields'] as $key => &$value)
			{
				if(!in_array($key, $fieldKeys))
				{
					continue;
				}

				if(!is_array($value))
				{
					$value = null;
					continue;
				}

				while(array_search($command->id, $value) !== false)
				{
					$index = array_search($command->id, $value);
					unset($value[$index]);
				}

				$value = array_values($value);
			}

			$params = [];
	        $params['index'] = $this->indexName;
	        $params['type'] = 'doc';
	        $params['id'] = $id;
	        $params['body'] = [];
	        $params['body']['doc'] = $body;

	        $this->client->update($params);
	        $changed = true;
		}

		if($changed)
		{
			Trunk::forgetType('entity');
		}
	}

}