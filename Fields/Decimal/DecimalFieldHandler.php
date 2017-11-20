<?php 
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Fields\Decimal;

use Cookbook\EntityElastic\Fields\AbstractFieldHandler;
use stdClass;

/**
 * DecimalFieldHandler class
 * 
 * Responsible for handling decimal field types
 * 
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	cookbook/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class DecimalFieldHandler extends AbstractFieldHandler {

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
		if(is_null($value))
		{
			return null;
		}
		$value = floatval($value);
		return $value;
	}

}