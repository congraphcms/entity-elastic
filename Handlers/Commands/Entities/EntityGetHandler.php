<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Handlers\Commands\Entities;


use Congraph\EntityElastic\Repositories\EntityRepository;
use Congraph\Core\Bus\RepositoryCommandHandler;
use Congraph\Core\Bus\RepositoryCommand;

/**
 * EntityGetHandler class
 * 
 * Handling command for getting entities
 * 
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class EntityGetHandler extends RepositoryCommandHandler
{

	/**
	 * Create new EntityGetHandler
	 * 
	 * @param Congraph\EntityElastic\Repositories\EntityRepository $repository
	 * 
	 * @return void
	 */
	public function __construct(EntityRepository $repository)
	{
		parent::__construct($repository);
	}

	/**
	 * Handle RepositoryCommand
	 * 
	 * @param Congraph\Core\Bus\RepositoryCommand $command
	 * 
	 * @return void
	 */
	public function handle(RepositoryCommand $command)
	{
		return $this->repository->get(
			(!empty($command->params['filter']))?$command->params['filter']:[],
			(!empty($command->params['offset']))?$command->params['offset']:0,
			(!empty($command->params['limit']))?$command->params['limit']:0,
			(!empty($command->params['sort']))?$command->params['sort']:[],
			(!empty($command->params['include']))?$command->params['include']:[],
			(!empty($command->params['locale']))?$command->params['locale']:null,
			(!empty($command->params['status']))?$command->params['status']:null
		);
	}
}