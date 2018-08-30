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

use Congraph\EntityElastic\Repositories\EntityRepositoryContract;
use Congraph\Core\Bus\RepositoryCommandHandler;
use Congraph\Core\Bus\RepositoryCommand;

/**
 * EntityFetchHandler class
 *
 * Handling command for fetching entity
 *
 *
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/entity-elastic
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class EntityFetchHandler extends RepositoryCommandHandler
{

    /**
     * Create new EntityFetchHandler
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
     * @param Congraph\Core\Bus\RepositoryCommand $command
     *
     * @return void
     */
    public function handle(RepositoryCommand $command)
    {
        $locale = (!empty($command->params['locale']))?$command->params['locale']:null;
        $include = (!empty($command->params['include']))?$command->params['include']:[];
        $status = (!empty($command->params['status']))?$command->params['status']:null;
        return $this->repository->fetch($command->id, $include, $locale, $status);
    }
}
