<?php 
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Fields\Datetime;

use Congraph\EntityElastic\Fields\AbstractFieldHandler;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use stdClass;

/**
 * DatetimeFieldHandler class
 * 
 * Responsible for handling datetime field types
 * 
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class DatetimeFieldHandler extends AbstractFieldHandler {

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
		$value = Carbon::parse($value)->tz('UTC')->toDateTimeString();
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
		
		$value = Carbon::parse($value);
		if(Config::get('app.timezone'))
		{
			$value->tz(Config::get('app.timezone'));
		}
		return $value;
	}
}