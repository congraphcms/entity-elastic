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

use Elasticsearch\Client;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;


/**
 * ESManager class
 *
 * Service for managing elastic search connections
 *
 * @author      Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright   Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package     congraph/entity-elastic
 * @since       1.0.0
 * @version     1.0.0
 */
class ESManager
{

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * The Elasticsearch connection factory instance.
     *
     * @var \Congraph\EntityElastic\Services\ESFactory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \Congraph\EntityElastic\Services\Factory $factory
     */
    public function __construct(Container $app, ESFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }

    /**
     * Retrieve or build the named connection.
     *
     * @param string|null $name
     *
     * @return \Elasticsearch\Client
     */
    public function connection(string $name = null): Client
    {
        $name = $name ?: $this->getDefaultConnection();

        if (!isset($this->connections[$name])) {
            $client = $this->makeConnection($name);

            $this->connections[$name] = $client;
        }

        return $this->connections[$name];
    }

    /**
     * Get the default connection.
     *
     * @return string
     */
    public function getDefaultConnection(): string
    {
        return $this->app['config']['cb.elastic.default_connection'];
    }

    /**
     * Set the default connection.
     *
     * @param string $connection
     */
    public function setDefaultConnection(string $connection): void
    {
        $this->app['config']['cb.elastic.default_connection'] = $connection;
    }

    /**
     * Make a new connection.
     *
     * @param string $name
     *
     * @return \Elasticsearch\Client
     */
    protected function makeConnection(string $name): Client
    {
        $config = $this->getConfig($name);

        return $this->factory->make($config);
    }

    /**
     * Get the configuration for a named connection.
     *
     * @param $name
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function getConfig(string $name)
    {
        $connections = $this->app['config']['cb.elastic.connections'];

        if (null === $config = Arr::get($connections, $name)) {
            throw new \InvalidArgumentException("Elasticsearch connection [$name] not configured.");
        }

        return $config;
    }

    /**
     * Return all of the created connections.
     *
     * @return array
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string $method
     * @param  array $parameters
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return call_user_func_array([$this->connection(), $method], $parameters);
    }
}