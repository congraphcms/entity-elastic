<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Fields;

use Congraph\Contracts\Eav\FieldHandlerFactoryContract;
use Congraph\Eav\Managers\AttributeManager;
use Illuminate\Contracts\Container\Container;

/**
 * Attribute Handler Factory class
 * 
 * Used to create suitable handler for different field types
 *
 * @uses  		Congraph\Contracts\Eav\FieldHandlerFactoryContract
 * @uses  		Congraph\Eav\Managers\AttributeManager
 * @uses 		Illuminate\Container\Container
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class FieldHandlerFactory implements FieldHandlerFactoryContract
{

	/**
	 * Laravel Container object.
	 *
	 * @var Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * AttributeManager
	 * 
	 * @var Congraph\Eav\Managers\AttributeManager
	 */
	public $attributeManager;

	/**
	 * List of handlers
	 * 
	 * @var array
	 */
	protected static $handlers = [];

	/**
	 * Create new AttributeHandlerFactory
	 * 
	 * @return void
	 */
	public function __construct(Container $container, AttributeManager $attributeManager)
	{

		// Inject dependencies
		$this->container = $container;
		$this->attributeManager = $attributeManager;

	}


	/**
	 * Make appropriate FieldHandler by attribute field type.
	 * Definition of FieldHandlers for each data type is found in config file.
	 * 
	 * @param string $attributeFieldType - field type of attribute
	 * 
	 * @return Congraph\Eav\Fields\AbstractFieldHandler
	 * 
	 * @throws InvalidArgumentException
	 */
	public function make($attributeFieldType)
	{
		$fieldSettings = $this->attributeManager->getFieldType($attributeFieldType);

		if(empty($fieldSettings['elastic_handler']))
		{
			throw new \InvalidArgumentException('Field type must have defined handler.');
		}

		$handler = $fieldSettings['elastic_handler'];

		if(!in_array($handler, self::$handlers)){
			self::$handlers[$handler] = $this->container->make($handler);
		}
		return self::$handlers[$handler];
	}
}