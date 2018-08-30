<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Validators\Entities;

use Congraph\Contracts\Eav\FieldValidatorFactoryContract;
use Congraph\Eav\Managers\AttributeManager;
use Congraph\Eav\Facades\MetaData;
use Congraph\Core\Bus\RepositoryCommand;
use Congraph\Core\Exceptions\BadRequestException;
use Congraph\Core\Exceptions\NotFoundException;
use Congraph\Core\Validation\Validator;
use Carbon\Carbon;


/**
 * EntityGetValidator class
 * 
 * Validating command for getting entities
 * 
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class EntityGetValidator extends Validator
{

	/**
	 * Available fields for sorting
	 *
	 * @var array
	 */
	protected $availableSorting;

	/**
	 * Available fields for filtering
	 *
	 * @var array
	 */
	protected $availableFilters;

	/**
	 * Default sorting criteria
	 *
	 * @var array
	 */
	protected $defaultSorting;
	
	/**
	 * Factory for field validators,
	 * makes appropriate field validator depending on field type
	 *
	 * @var Congraph\Contracts\Eav\FieldValidatorFactoryContract
	 */
	protected $fieldValidatorFactory;

	/**
	 * Helper for attributes
	 * 
	 * @var Congraph\Eav\Managers\AttributeManager
	 */
	protected $attributeManager;
	

	/**
	 * Create new AttributeGetValidator
	 * 
	 * @return void
	 */
	public function __construct(
		AttributeManager $attributeManager, 
		FieldValidatorFactoryContract $fieldValidatorFactory
	)
	{

		$this->availableSorting = [
			'id',
			'type',
			'entity_type_id',
			'attribute_set',
			'attribute_set_id',
			'created_at',
			'updated_at'
		];

		$this->availableFilters = [
			'id' 					=> ['e', 'ne', 'lt', 'lte', 'gt', 'gte', 'in', 'nin'],
			'type_id'				=> ['e', 'ne', 'lt', 'lte', 'gt', 'gte', 'in', 'nin'],
			'type'					=> ['e', 'ne', 'in', 'nin'],
			'entity_type'			=> ['e', 'ne', 'in', 'nin'],
			'entity_type_id'		=> ['e', 'ne', 'in', 'nin'],
			'attribute_set'			=> ['e', 'ne', 'in', 'nin'],
			'attribute_set_id'		=> ['e', 'ne', 'in', 'nin'],
			'created_at'			=> ['lt', 'lte', 'gt', 'gte'],
			'updated_at'			=> ['lt', 'lte', 'gt', 'gte'],
		];

		$this->dateFields = [
			'created_at',
			'updated_at'
		];

		$this->defaultSorting = ['-created_at'];

		parent::__construct();
		
		$this->fieldValidatorFactory = $fieldValidatorFactory;

		$this->attributeManager = $attributeManager;
	}


	/**
	 * Validate RepositoryCommand
	 * 
	 * @param Congraph\Core\Bus\RepositoryCommand $command
	 * 
	 * @todo  Create custom validation for all db related checks (DO THIS FOR ALL VALIDATORS)
	 * @todo  Check all db rules | make validators on repositories
	 * 
	 * @return void
	 */
	public function validate(RepositoryCommand $command)
	{

		if( isset($command->params['locale']) )
		{
			try
			{
				MetaData::getLocaleByIdOrCode($command->params['locale']);
			}
			catch(NotFoundException $e)
			{
				$e = new BadRequestException();
				$e->setErrorKey('locale.');
				$e->addErrors('Invalid locale.');

				throw $e;
			}
		}

		if( ! empty($command->params['filter']) )
		{
			$this->validateFilters($command->params['filter']);

			if( ! isset($command->params['filter']['entity_type_id']) && isset($command->params['filter']['type']) )
			{
				$types = [];
				if( ! is_array($command->params['filter']['type']) )
				{
					$types[] = $command->params['filter']['type'];
				}
				else
				{
					foreach ($command->params['filter']['type'] as $operation => $filter)
					{
						if( ! is_array($filter) )
						{
							$filter = explode(',', strval($filter));
						}

						foreach ($filter as $type)
						{
							$types[] = $type;
						}
					}
				}
				if( ! empty($types) )
				{
					$entityTypes = MetaData::getEntityTypes();

					$entityTypesByCode = [];
					foreach ($entityTypes as $entityType)
					{
						if(!in_array($entityType->code, $types))
						{
							continue;
						}
						$entityTypesByCode[$entityType->code] = $entityType;
					}
					if( ! is_array($command->params['filter']['type']) )
					{
						if(array_key_exists($command->params['filter']['type'], $entityTypesByCode))
						{
							$command->params['filter']['entity_type_id'] = $entityTypesByCode[$command->params['filter']['type']]->id;
						}
						else
						{
							$command->params['filter']['entity_type_id'] = 0;
						}
					}
					else
					{
						foreach ($command->params['filter']['type'] as $operation => $filter)
						{
							if($operation == 'in' || $operation == 'nin')
							{
								if( ! is_array($filter) )
								{
									$filter = explode(',', strval($filter));
								}
								foreach ($filter as $type)
								{
									if( ! isset($command->params['filter']['entity_type_id']) || ! is_array($command->params['filter']['entity_type_id']) )
									{
										$command->params['filter']['entity_type_id'] = [];
									}
									if( ! isset($command->params['filter']['entity_type_id'][$operation]) || ! is_array($command->params['filter']['entity_type_id'][$operation]) )
									{
										$command->params['filter']['entity_type_id'][$operation] = [];
									}
									if(array_key_exists($type, $entityTypesByCode))
									{
										$command->params['filter']['entity_type_id'][$operation] = array_merge($command->params['filter']['entity_type_id'][$operation], [$entityTypesByCode[$type]->id]);
									}
									else
									{
										$command->params['filter']['entity_type_id'][$operation] = array_merge($command->params['filter']['entity_type_id'][$operation], [0]);
									}
								}
								continue;
							}

							if(array_key_exists($filter, $entityTypesByCode))
							{
								$command->params['filter']['entity_type_id'] = $entityTypesByCode[$filter]->id;
							}
							else
							{
								$command->params['filter']['entity_type_id'] = 0;
							}
						}
					}
				}
			}

			if( ! isset($command->params['filter']['attribute_set_id']) && isset($command->params['filter']['attribute_set']) )
			{
				$sets = [];
				if( ! is_array($command->params['filter']['attribute_set']) )
				{
					$sets[] = $command->params['filter']['attribute_set'];
				}
				else
				{
					foreach ($command->params['filter']['attribute_set'] as $operation => $filter)
					{
						if( ! is_array($filter) )
						{
							$filter = explode(',', strval($filter));
						}

						foreach ($filter as $set)
						{
							$sets[] = $set;
						}
					}
				}
				if( ! empty($sets) )
				{
					$attributeSets = MetaData::getAttributeSets();

					$attributeSetsByCode = [];
					foreach ($attributeSets as $attributeSet)
					{
						if(!in_array($attributeSet->code, $sets))
						{
							continue;
						}

						$attributeSetsByCode[$attributeSet->code] = $attributeSet;
					}
					if( ! is_array($command->params['filter']['attribute_set']) )
					{
						if(array_key_exists($command->params['filter']['attribute_set'], $attributeSetsByCode))
						{
							$command->params['filter']['attribute_set_id'] = $attributeSetsByCode[$command->params['filter']['attribute_set']]->id;
						}
						else
						{
							$command->params['filter']['attribute_set_id'] = 0;
						}
					}
					else
					{
						foreach ($command->params['filter']['attribute_set'] as $operation => $filter)
						{
							if($operation == 'in' || $operation == 'nin')
							{
								if( ! is_array($filter) )
								{
									$filter = explode(',', strval($filter));
								}
								foreach ($filter as $attribute_set)
								{
									if( ! is_array($command->params['filter']['attribute_set_id']) )
									{
										$command->params['filter']['attribute_set_id'] = [];
									}
									if( ! is_array($command->params['filter']['attribute_set_id'][$operation]) )
									{
										$command->params['filter']['attribute_set_id'][$operation] = [];
									}
									if(array_key_exists($attribute_set, $attributeSetsByCode))
									{
										$command->params['filter']['attribute_set_id'][$operation] = array_merge($command->params['filter']['attribute_set_id'][$operation], [$attributeSetsByCode[$attribute_set]->id]);
									}
									else
									{
										$command->params['filter']['attribute_set_id'][$operation] = array_merge($command->params['filter']['attribute_set_id'][$operation], [0]);
									}
								}
							}

							if(array_key_exists($filter, $attributeSetsByCode))
							{
								$command->params['filter']['attribute_set_id'] = $attributeSetsByCode[$filter]->id;
							}
							else
							{
								$command->params['filter']['attribute_set_id'] = 0;
							}
						}
					}
				}
			}

			unset($command->params['filter']['type']);
			unset($command->params['filter']['attribute_set']);
		}

		if( empty($command->params['offset']) )
		{
			$command->params['offset'] = 0;
		}
		if( empty($command->params['limit']) )
		{
			$command->params['limit'] = 0;
		}
		$this->validatePaging($command->params['offset'], $command->params['limit']);
		
		if( ! empty($command->params['sort']) )
		{
			$this->validateSorting($command->params['sort']);
		}

		if( ! empty($command->params['status']) )
		{
			$this->validateStatus($command->params['status']);
		}
		
	}

	protected function validateStatus(&$status)
	{
		if( ! is_array($status) )
		{
			return;
		}

		foreach ($status as $operation => &$value)
		{
			if( ! in_array($operation, ['e', 'ne', 'in', 'nin']) )
			{
				$e = new BadRequestException();
				$e->setErrorKey('status');
				$e->addErrors('Status operation is not allowed.');

				throw $e;
			}

			if($operation == 'in' || $operation == 'nin')
			{
				if( ! is_array($value) )
				{
					$value = explode(',', strval($value));
				}
			}
			else
			{
				if( is_array($value) || is_object($value))
				{
					$e = new BadRequestException();
					$e->setErrorKey('status');
					$e->addErrors('Invalid status.');
				}
			}
		}
	}

	protected function validateFilters(&$filters)
	{
		if( ! empty($filters) )
		{
			if(is_string($filters))
			{
				$objFilters = json_decode($filters, true);
 				if(json_last_error() == JSON_ERROR_NONE)
 				{
 					$filters = $objFilters;
 				}
 				else
 				{
 					$e = new BadRequestException();
					$e->setErrorKey('filter');
					$e->addErrors('Invalid filter format.');

					throw $e;
 				}
			}

			$fieldFilters = [];
			foreach ($filters as $field => &$filter)
			{

				// check for field filters
				// 
				if(substr( $field, 0, 7 ) === "fields.")
				{
					$code = substr($field, 7);
					$fieldFilters[$code] = $filter;
					continue;
				}

				if($field == 's')
				{
					$filter = strval($filter);
					if(empty($filter))
					{
						unset($filters[$field]);
					}
					continue;
				}


				if( ! array_key_exists($field, $this->availableFilters) )
				{
					$e = new BadRequestException();
					$e->setErrorKey('filter.' . $field);
					$e->addErrors('Filtering by \'' . $field . '\' is not allowed.');

					throw $e;
				}



				if( ! is_array($filter) )
				{
					if( ! in_array('e', $this->availableFilters[$field]) )
					{
						$e = new BadRequestException();
						$e->setErrorKey('filter.' . $field);
						$e->addErrors('Filter operation is not allowed.');

						throw $e;
					}

					continue;
				}



				foreach ($filter as $operation => &$value)
				{
					if( ! in_array($operation, $this->availableFilters[$field]) )
					{
						$e = new BadRequestException();
						$e->setErrorKey('filter.' . $field);
						$e->addErrors('Filter operation is not allowed.');

						throw $e;
					}

					if(in_array($field, $this->dateFields))
					{
						$value = Carbon::parse(strval($value))->tz('UTC')->toDateTimeString();
					}

					if($operation == 'in' || $operation == 'nin')
					{
						if( ! is_array($value) )
						{
							$value = explode(',', strval($value));
						}
						foreach ($value as $index => &$item)
						{
							$item = ltrim(rtrim(strval($item)));
							if(empty($item))
							{
								unset($value[$index]);
							}
						}
						$value = array_values($value);
						if(empty($value))
						{
							unset($filter[$operation]);
						}
					}
					else
					{
						if( is_array($value) || is_object($value))
						{
							$e = new BadRequestException();
							$e->setErrorKey('filter.' . $field);
							$e->addErrors('Invalid filter.');
						}
					}
				}
			}

			if( ! empty($fieldFilters) )
			{
				$attributes = MetaData::getAttributes();
				$attributeCodes = [];
				foreach ($attributes as $attribute)
				{
					if(!isset($fieldFilters[$attribute->code]))
					{
						continue;
					}

					$fieldValidator = $this->fieldValidatorFactory->make($attribute->field_type);
					$fieldValidator->validateFilter($fieldFilters[$attribute->code], $attribute);
					$filters['fields.' . $attribute->code] = $fieldFilters[$attribute->code];
					$attributeCodes[] = $attribute->code;
				}

				foreach ($fieldFilters as $code => $f)
				{
					if( ! in_array($code, $attributeCodes) )
					{
						$e = new BadRequestException();
						$e->setErrorKey('filter.fields.' . $code);
						$e->addErrors('Filtering by \'' . $code . '\' is not allowed.');

						throw $e;
					}
				}
			}
			

			return;
		}

		$filters = [];
	}

	protected function validatePaging(&$offset = 0, &$limit = 0)
	{
		$offset = intval($offset);
		$limit = intval($limit);
	}

	protected function validateSorting(&$sort)
	{
		if( empty($sort) )
		{
			$sort = $this->defaultSorting;
			return;
		}

		if( ! is_array($sort) )
		{
			$sort = explode(',', strval($sort));
		}

		$fieldSorting = [];
		foreach ($sort as $criteria)
		{
			if( empty($criteria) )
			{
				continue;
			}

			if( $criteria[0] === '-' )
			{
				$criteria = substr($criteria, 1);
			}

			if(substr( $criteria, 0, 7 ) === "fields.")
			{
				$code = substr($criteria, 7);
				$fieldSorting[] = $code;
				continue;
			}

			if( ! in_array($criteria, $this->availableSorting) )
			{
				$e = new BadRequestException();
				$e->setErrorKey('sort.' . $criteria);
				$e->addErrors('Sorting by \'' . $criteria . '\' is not allowed.');

				throw $e;
			}
		}

		if( ! empty($fieldSorting) )
		{
			$attributes = MetaData::getAttributes();
			$attributesByCode = [];
			$attributeCodes = [];
			foreach ($attributes as $attribute)
			{
				if(!in_array($attribute->code, $fieldSorting))
				{
					continue;
				}

				$attributeSettings = $this->attributeManager->getFieldType($attribute->field_type);
				if( ! isset($attributeSettings['sortable']) || ! $attributeSettings['sortable'] )
				{
					$e = new BadRequestException();
					$e->setErrorKey('sort.fields.' . $attribute->code);
					$e->addErrors('Sorting by \'' . $attribute->code . '\' is not allowed.');

					throw $e;
				}

				$attributeCodes[] = $attribute->code;
			}
			
			foreach ($fieldSorting as $code)
			{
				if( ! in_array($code, $attributeCodes) )
				{
					$e = new BadRequestException();
					$e->setErrorKey('sort.fields.' . $code);
					$e->addErrors('Sorting by \'' . $code . '\' is not allowed.');

					throw $e;
				}
			}
		}
	}
}