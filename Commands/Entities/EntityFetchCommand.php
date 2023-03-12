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
 * EntityFetchCommand class
 * 
 * Command for fetching entity
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class EntityFetchCommand extends RepositoryCommand
{

    /**
     * Create new EntityFetchCommand
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
	 * @return Congraph/Core/Repositories/Model
     */
    public function handle()
    {
        $locale = (!empty($this->params['locale']))?$this->params['locale']:null;
        $include = (!empty($this->params['include']))?$this->params['include']:[];
        $status = (!empty($this->params['status']))?$this->params['status']:null;
        return $this->repository->fetch($this->id, $include, $locale, $status);
    }

}