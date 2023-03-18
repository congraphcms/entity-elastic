<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Congraph\EntityElastic\Services;

use Congraph\Core\Exceptions\Exception;
use Congraph\Core\Exceptions\NotFoundException;
use Congraph\Core\Exceptions\BadRequestException;
use Congraph\Core\Repositories\Collection;
use Congraph\Core\Repositories\Model;
use Congraph\Eav\Managers\AttributeManager;
use Congraph\Eav\Facades\MetaData;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Carbon\Carbon;
use stdClass;

/**
 * ESFactory class
 *
 * Factory for elastic search client
 *
 * @author      Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright   Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package     congraph/entity-elastic
 * @since       1.0.0
 * @version     1.0.0
 */
class ESFactory
{
    /**
     * Make the Elasticsearch client for the given named configuration, or
     * the default client.
     *
     * @param array $config
     *
     * @return \Elasticsearch\Client
     */
    public function make(array $config): Client
    {
        return $this->buildClient($config);
    }

    /**
     * Build and configure an Elasticsearch client.
     *
     * @param array $config
     *
     * @return \Elasticsearch\Client
     */
    protected function buildClient(array $config): Client
    {
        $clientBuilder = ClientBuilder::create();

        // Configure hosts
        $clientBuilder->setHosts($config['hosts']);

        $esConfig = Config::get('cb.elastic');

        // Configure logging
        if (Arr::get($esConfig, 'logging')) {

            $logPath = Arr::get($esConfig, 'logPath');
            $logLevel = Arr::get($esConfig, 'logLevel');
            if ($logPath && $logLevel) {
                $handler = new StreamHandler($logPath, $logLevel);
                $logObject = new Logger('log');
                $logObject->pushHandler($handler);
                $clientBuilder->setLogger($logObject);
            }
        }

        return $clientBuilder->build();

    }
}