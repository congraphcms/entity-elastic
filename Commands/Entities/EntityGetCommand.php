<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Commands\Entities;

use Congraph\Core\Bus\RepositoryCommand;
use Congraph\EntityElastic\Repositories\EntityRepositoryContract;

/**
 * EntityGetCommand class
 * 
 * Command for getting entities
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class EntityGetCommand extends RepositoryCommand
{

    /**
	 * Create new EntityGetCommand
	 * 
	 * @param Congraph\EntityElastic\Repositories\EntityRepositoryContract $repository
	 * 
	 * @return void
	 */
	public function __construct(EntityRepositoryContract $repository)
	{
		parent::__construct($repository);
	}

	/**
	 * Handle RepositoryCommand
	 * 
	 * 
	 * @return Congraph/Core/Repositories/Collection
	 */
	public function handle()
	{
		return $this->repository->get(
			(!empty($this->params['filter']))?$this->params['filter']:[],
			(!empty($this->params['offset']))?$this->params['offset']:0,
			(!empty($this->params['limit']))?$this->params['limit']:0,
			(!empty($this->params['sort']))?$this->params['sort']:[],
			(!empty($this->params['include']))?$this->params['include']:[],
			(!empty($this->params['locale']))?$this->params['locale']:null,
			(!empty($this->params['status']))?$command->params['status']:null
		);
	}
}