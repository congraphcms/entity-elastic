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
use Congraph\EntityElastic\Repositories\EntityRepositoryContract as EntityElasticRepositoryContract;
use Congraph\Core\Repositories\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Throwable;


final class IndexAllCommand extends Command
{
    private const BATCH_SIZE = 10;

    /**
     * @var string
     */
    protected $signature = 'congraph:es:index-all';

    /**
     * @var \Congraph\Contracts\Eav\EntityRepositoryContract
     */
    private EntityRepositoryContract $repository;

    /**
     * @var \Congraph\EntityElastic\Repositories\EntityRepositoryContract
     */
    private EntityElasticRepositoryContract $elasticRepository;

    public function __construct(
        EntityRepositoryContract $repo,
        EntityElasticRepositoryContract $elasticRepository
    ) {
        $this->repository = $repo;
        $this->elasticRepository = $elasticRepository;

        parent::__construct();
    }

    public function handle(): int
    {
        Config::set('cb.elastic.indexing', true);
        try {
            $page = 0;
            $bar = null;

            while (true) {
                $result = $this->getBatch($page);
                $total = (int) $result->getMeta('total');
                $count = (int) $result->getMeta('count');

                if ($bar === null) {
                    $bar = $this->output->createProgressBar($total);
                }

                foreach ($result as $entity) {
                    $this->elasticRepository->create($entity);
                    $bar->advance();
                }

                if ($count === 0 || ($page + 1) * self::BATCH_SIZE >= $total) {
                    break;
                }

                $page++;
            }

            if ($bar !== null) {
                $bar->finish();
                $this->newLine();
            }
        } catch (Throwable $e) {
            $this->newLine();
            $this->error(
                sprintf(
                    'Error: %s.',
                    $e->getMessage()
                )
            );

            throw $e;
        } finally {
            Config::set('cb.elastic.indexing', false);
        }

        return self::SUCCESS;
    }

    private function getBatch(int $page = 0): Collection {
        return $this->repository->get([], $page * self::BATCH_SIZE, self::BATCH_SIZE, ['created_at', 'id'], [], null, null);
    }
}
