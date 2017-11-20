<?php
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Fields;

use Cookbook\Contracts\Eav\AttributeRepositoryContract;
use Cookbook\Core\Traits\ErrorManagerTrait;
use Cookbook\Eav\Managers\AttributeManager;

use Cookbook\EntityElastic\Traits\ElasticQueryBuilderTrait;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Config;

/**
 * Abstract Field Handler class
 * 
 * Base class for all feild handlers
 * 
 * @uses  		Cookbook\Core\Traits\ErrorManagerTrait
 * @uses  		Cookbook\Eav\Managers\AttributeManager
 * @uses 		Illuminate\Database\Connection
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	cookbook/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
abstract class AbstractFieldHandler
{
	use ErrorManagerTrait;
	use ElasticQueryBuilderTrait;

	/**
     * Elasticsearch client
     *
     * @var \Elasticsearch\Client
     */
    protected $client;

	/**
	 * AttributeManager
	 * 
	 * @var AttributeManager
	 */
	public $attributeManager;

	/**
	 * Create new AbstractAttributeHandler
	 * 
	 * @param Illuminate\Database\Connection 			$db
	 * @param Cookbook\Eav\Managers\AttributeManager 	$attributeManager
	 * @param string 									$table
	 *  
	 * @return void
	 */
	public function __construct(ClientBuilder $elasticClientBuilder, AttributeManager $attributeManager)
	{
		$this->attributeManager = $attributeManager;
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
		return $value;
	}

	/**
	 * Parse filter for database use
	 * 
	 * @param mixed $value
	 * @param object $attribute
	 * 
	 * @return boolean
	 */
	public function parseFilter($filter, $attribute)
	{
		return $this->parseValue($filter, $attribute, null, null, null);
	}



	public function prepareForElastic($value, $attribute, $locale, $params, $entity)
	{
		$attributeSettings = $this->attributeManager->getFieldTypes()[$attribute->field_type];

		if($attributeSettings['has_multiple_values'])
		{
			if( ! is_array($value) )
			{
				$v = [];
				if(!empty($value))
				{
					$v[] = $value;
				}
				$value = $v;
			}

			foreach ($value as &$item)
			{
				$item = $this->parseValue($item, $attribute, $locale, $params, $entity);
			}

			return $value;
		}
		
		return $value = $this->parseValue($value, $attribute, $locale, $params, $entity);
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

		if($attribute->localized)
		{
			if($locale)
			{
				$code = $code . '__' . $locale->code;
			}
			else
			{
				$code = $code . '__*';
			}
		}

		if( ! is_array($filter) )
		{
			$filter = $this->parseFilter($filter, $attribute);
			$query = $this->addTermQuery($query, 'fields.' . $code, $filter);
		}
		else
		{
			$query = $this->parseFilterOperator($query, 'fields.' . $code, $filter);
		}

		return $query;
	}

}