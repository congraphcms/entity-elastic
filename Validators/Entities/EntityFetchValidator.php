<?php
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Validators\Entities;

use Cookbook\EntityElastic\Repositories\EntityRepository;
use Cookbook\Eav\Facades\MetaData;
use Cookbook\Core\Bus\RepositoryCommand;
use Cookbook\Core\Exceptions\ValidationException;
use Cookbook\Core\Validation\Validator;

/**
 * EntityFetchValidator class
 *
 * Validating command for fetching entities
 *
 *
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	cookbook/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class EntityFetchValidator extends Validator
{

	/**
	 * Repository for entities
	 * 
	 * @var Cookbook\EntityElastic\Repositories\EntityRepository
	 */
	protected $entityRepository;

	/**
	 * Repository for locales
	 * 
	 * @var Cookbook\Contracts\Locales\LocaleRepositoryContract
	 */
	protected $localeRepository;

	/**
	 * Create new EntityFetchValidator
	 *
	 * @return void
	 */
	public function __construct(EntityRepository $entityRepository)
	{
		$this->entityRepository = $entityRepository;

		parent::__construct();

		$this->exception->setErrorKey('entities');
	}


	/**
	 * Validate RepositoryCommand
	 *
	 * @param Cookbook\Core\Bus\RepositoryCommand $command
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
}
