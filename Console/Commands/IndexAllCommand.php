<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Console\Commands;

use Congraph\Contracts\Eav\EntityRepositoryContract;
use Congraph\Core\Repositories\Collection;
use Elasticsearch\Client;
use Illuminate\Console\Command;
use Throwable;


final class IndexAllCommand extends Command
{

    /**
     * @var string
     */
    protected $signature = 'congraph:es:index-all';

    /**
     * @var \Congraph\Contracts\Eav\EntityRepositoryContract
     */
    private $repository;

    public function __construct(
        EntityRepositoryContract $repo
    ) {
        $this->repository = $repo;

        parent::__construct();
    }

    public function handle(): int
    {

        try {
            $result = $this->getBatch();
            $total = $result->getMeta('total');
            $count = $result->getMeta('count');
            $this->info("Fetched {$count} of {$total} records");
        } catch (Throwable $e) {
            $this->writeln();
            $this->error(
                sprintf(
                    'Error: %s.',
                    $e->getMessage()
                )
            );

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getBatch(int $page = 0): Collection {
        $filter = []; $offset = 0; $limit = 0; $sort = []; $include = []; $locale = null; $status = null;
        return $this->repository->get([], $page * 10, 10, ['id'], [], null, null);
    }
}