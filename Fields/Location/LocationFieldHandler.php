<?php 
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Fields\Location;

use Congraph\EntityElastic\Fields\AbstractFieldHandler;
use stdClass;

/**
 * LocationFieldHandler class
 *
 * Responsible for handling location field types
 *
 *
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class LocationFieldHandler extends AbstractFieldHandler
{

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
        // $value = json_encode($value);
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
        
        // $value = json_decode($value);
        return $value;
    }
}
