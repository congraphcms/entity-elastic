<?php 
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Fields\Text;

use Congraph\EntityElastic\Fields\AbstractFieldHandler;
use stdClass;

/**
 * TextFieldHandler class
 * 
 * Responsible for handling text field types
 * 
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class TextFieldHandler extends AbstractFieldHandler {

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
		if($value !== null)
		{
			$value = strval($value);
		}
		return $value;
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
			$query = $this->addTermQuery($query, 'fields.' . $code . '.keyword', $filter);
		}
		else
		{
			$query = $this->parseFilterOperator($query, 'fields.' . $code, $filter, true);
		}

		return $query;
	}
}