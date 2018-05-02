<?php 
/*
 * This file is part of the cookbook/eav package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Fields\Compound;

use Cookbook\EntityElastic\Fields\AbstractFieldHandler;
use Illuminate\Support\Facades\Config;
use Cookbook\Eav\Managers\AttributeManager;
use Cookbook\Contracts\Eav\AttributeRepositoryContract;
use Cookbook\EntityElastic\Repositories\EntityRepository;
use Illuminate\Database\Connection;
use Cookbook\Core\Exceptions\BadRequestException;
use Cookbook\Eav\Facades\MetaData;
use Illuminate\Support\Facades\Event;
use Elasticsearch\ClientBuilder;
use Cookbook\Core\Exceptions\NotFoundException;
use \Exception;

/**
 * CompoundFieldHandler class
 *
 * Responsible for handling compound field types
 *
 *
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	cookbook/eav
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class CompoundFieldHandler extends AbstractFieldHandler
{
	protected static $waitingForMultiLocaleUpdate = false;


	/**
	 * Repository for entities
	 *
	 * @var Cookbook\Contracts\Eav\EntityRepository
	 */
	public $entityRepository;

	/**
	 * Create new CompoundFieldHandler
	 *
	 * @param Illuminate\Database\Connection 			$db
	 * @param Cookbook\Eav\Managers\AttributeManager 	$attributeManager
	 * @param string 									$table
	 *
	 * @return void
	 */
	public function __construct(
		ClientBuilder $elasticClientBuilder,
		AttributeManager $attributeManager,
		EntityRepository $entityRepository
	) {
		// Inject dependencies
		$this->attributeManager = $attributeManager;
		$this->entityRepository = $entityRepository;
		// Init empty MessagBag object for errors
		$this->setErrors();

		$hosts = Config::get('cb.elastic.hosts');
		$prefix = Config::get('cb.elastic.index_prefix');
		$this->indexName = $prefix . 'entities';

		$this->client = $elasticClientBuilder->create()
			->setHosts($hosts)
			->build();
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
		// var_dump([$value, $params]);
		$inputs = $attribute->data->inputs;
		$value = $this->calculate($inputs, $locale, $params, $entity);
		$value = $this->getExpectedValue($value, $attribute);
		return $value;
	}

	protected function calculate($inputs, $locale, $params, $entity)
	{
		$localeCode = false;
		if ($locale) {
			$localeCode = MetaData::getLocaleById($locale)->code;
		}
		$inputs = array_values($inputs);
		$reverseInputs = array_reverse($inputs);
		$provisionalValue = null;
		foreach ($reverseInputs as $key => $input) {
			switch ($input->type) {
				case 'literal':
					$provisionalValue = $input->value;
					break;
				case 'field':
					// somewhat sketchy (locales and stuff)
					
					// get field value
					$attribute = MetaData::getAttributeById($input->value);
					$code = $attribute->code;
					$localized = $attribute->localized;

					$fieldValue = null;
					$takeFromEntity = true;
					if (array_key_exists('fields', $params) && is_array($params['fields']) && array_key_exists($code, $params['fields'])) {
						// var_dump('calculate from params - ' . $localeCode . ' - ' . $locale);
						// var_dump($params['fields'][$code]);
						if ($localeCode && is_array($params['fields'][$code]) && array_key_exists($localeCode, $params['fields'][$code])) {
							// var_dump('localized params');
							$fieldValue = $params['fields'][$code][$localeCode];
							$takeFromEntity = false;
						} else {
							// var_dump('flat params');
							$fieldValue = $params['fields'][$code];
							$takeFromEntity = false;
						}
					}

					if ($entity && $takeFromEntity) {
						// var_dump('calculate from entity - ' . $localeCode . ' - ' . $locale);
						if ($localized && $localeCode) {
							$code = $code . '__' . $localeCode;
						}
						if (array_key_exists($code, $entity['_source']['fields'])) {
							$fieldValue = $entity['_source']['fields'][$code];
						}
					}

					$provisionalValue = $fieldValue;
					break;
				case 'operator':
					switch ($input->value) {
						case 'CONCAT':
							$remainingInputs = array_slice($inputs, 0, count($inputs) - 1 - $key);
							$fieldValue = $this->calculate($remainingInputs, $locale, $params, $entity);
							// var_dump('CONCAT values');
							// var_dump($fieldValue);
							// var_dump($provisionalValue);
							return $fieldValue . $provisionalValue;
							break;

						default:
							throw new BadRequestException('Invalid compound field operator');
							break;
					}
					break;
				default:
					throw new BadRequestException('Invalid compound field input');
					break;
			}
		}

		return $provisionalValue;
	}

	protected function getExpectedValue($value, $attribute)
	{
		$expectedValue = $attribute->data->expected_value;

		switch ($expectedValue) {
			case 'string':
				return strval($value);
				break;

			default:
				throw new BadRequestException('Invalid compound field expected value');
				break;
		}
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
		return $this->getExpectedValue($value, $attribute);
	}

	public function onBeforeEntityUpdate($command)
	{
		$query = [
			'index' => $this->indexName,
			'type' => 'doc',
			'id' => $command->id
		];

		try {
			$entity = $this->client->get($query);
		} catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
			// throw new NotFoundException(['Entity not found.']);
			return;
		}

		$attributeSet = MetaData::getAttributeSetById($entity['_source']['attribute_set_id']);
		foreach ($attributeSet->attributes as $setAttribute) {
			$attribute = MetaData::getAttributeById($setAttribute->id);

			if ($attribute->field_type !== 'compound') {
				continue;
			}

			if (!$attribute->localized) {
				$command->params['fields'][$attribute->code] = null;
				continue;
			}

			if (isset($command->params['locale'])) {
				$command->params['fields'][$attribute->code] = null;
				foreach ($attribute->data->inputs as $input) {
					if ($input->type != 'field') {
						continue;
					}

					$attr = MetaData::getAttributeById($input->value);
					if ($attr && !$attr->localized && array_key_exists($attr->code, $command->params['fields'])) {
						self::$waitingForMultiLocaleUpdate = true;
					}
				}
				continue;
			}

			$locales = MetaData::getLocales();
			$command->params['fields'][$attribute->code] = [];
			foreach ($locales as $locale) {
				$command->params['fields'][$attribute->code][$locale->code] = null;
			}
		}
	}

	public function onAfterEntityUpdate($command, $result)
	{
		if (!self::$waitingForMultiLocaleUpdate) {
			return;
		}

		self::$waitingForMultiLocaleUpdate = false;

		$query = [
			'index' => $this->indexName,
			'type' => 'doc',
			'id' => $command->id
		];

		try {
			$entity = $this->client->get($query);
		} catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
			throw new NotFoundException(['Entity not found.']);
		}

		$body = $entity['_source'];

		$locales = MetaData::getLocales();
		$attributeSet = MetaData::getAttributeSetById($result->attribute_set_id);
		foreach ($attributeSet->attributes as $setAttribute) {
			$attribute = MetaData::getAttributeById($setAttribute->id);

			if ($attribute->field_type != 'compound' || !$attribute->localized) {
				continue;
			}

			$skipAttribute = true;
			foreach ($attribute->data->inputs as $input) {
				if ($input->type != 'field') {
					continue;
				}

				$attr = MetaData::getAttributeById($input->value);
				if ($attr && !$attr->localized && array_key_exists($attr->code, $command->params['fields'])) {
					$skipAttribute = false;
					break;
				}
			}

			if ($skipAttribute) {
				continue;
			}

			foreach ($locales as $locale) {
				$body['fields'][$attribute->code . '__' . $locale->code] = $this->parseValue(null, $attribute, $locale->id, [], $entity);
			}
		}

		if ($body == $entity['_source']) {
			return;
		}

		$params = [
			'index' => $this->indexName,
			'type' => 'doc',
			'id' => $command->id
		];

		$params['body'] = [];
		$params['body']['doc'] = $body;
		$this->client->update($params);

		// // var_dump("COMPUND UPDATE");
		$result = $this->entityRepository->fetch($result->id, [], $result->locale);

		return $result;
	}
}
