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
use Carbon\Carbon;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use stdClass;

/**
 * ESMapper class
 *
 * Service for handling elastic search mappings for entities
 *
 * @author      Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright   Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package     congraph/entity-elastic
 * @since       1.0.0
 * @version     1.0.0
 */
class ESMapper
{
    

}