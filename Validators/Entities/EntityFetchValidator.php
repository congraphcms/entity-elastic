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

use Congraph\EntityElastic\Repositories\EntityRepository;
use Congraph\Eav\Facades\MetaData;
use Congraph\Core\Bus\RepositoryCommand;
use Congraph\Core\Exceptions\ValidationException;
use Congraph\Core\Validation\Validator;

/**
 * EntityFetchValidator class
 *
 * Validating command for fetching entities
 *
 *
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class EntityFetchValidator extends Validator
{

	/**
	 * Repository for entities
	 * 
	 * @var Congraph\EntityElastic\Repositories\EntityRepository
	 */
	protected $entityRepository;

	/**
	 * Repository for locales
	 * 
	 * @var Congraph\Contracts\Locales\LocaleRepositoryContract
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
