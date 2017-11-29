<?php 
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cookbook\EntityElastic\Traits;

use Cookbook\Core\Exceptions\BadRequestException;
use Exception;
use Closure;
use stdClass;
/**
 * MapperTrait for mapping events/handlers/commands
 * 
 * Gives class ability to map events/handlers/commands
 * 
 * @author      Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright   Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package     cookbook/entity-elastic
 * @since       0.1.0-alpha
 * @version     0.1.0-alpha
 */
trait ElasticQueryBuilderTrait
{

    protected function parseFilterOperator($query, $key, $filter, $textValue = false)
    {
        foreach ($filter as $operator => $value) {
            switch (true) {
                case ($operator === 'e'):
                    if($textValue)
                    {
                        $key .= '.keyword';
                    }
                    return $this->addTermQuery($query, $key, $value);
                case ($operator === 'ne'):
                    if($textValue)
                    {
                        $key .= '.keyword';
                    }
                    return $this->addNotTermQuery($query, $key, $value);
                case ($operator === 'lt'):
                    return $this->addRangeQuery($query, $key, $value, $operator);
                case ($operator === 'lte'):
                    return $this->addRangeQuery($query, $key, $value, $operator);
                case ($operator === 'gt'):
                    return $this->addRangeQuery($query, $key, $value, $operator);
                case ($operator === 'gte'):
                    return $this->addRangeQuery($query, $key, $value, $operator);
                case ($operator === 'in'):
                    if($textValue)
                    {
                        $key .= '.keyword';
                    }
                    $value = $this->parseCommaValue($value);
                    return $this->addTermsQuery($query, $key, $value);
                case ($operator === 'nin'):
                    $value = $this->parseCommaValue($value);
                    if($textValue)
                    {
                        $key .= '.keyword';
                    }
                    return $this->addNotTermsQuery($query, $key, $value);
                case ($operator === 'm'):
                    $words = explode(' ', $value);
                    foreach ($words as $word)
                    {
                        if($textValue)
                        {
                            $termKey = $key . '.keyword';
                        }
                        $query = $this->addShouldTermQuery($query, $termKey, $word, 0);
                    }
                    return $this->addMatchQuery($query, $key, $value);
                default:
                    throw new BadRequestException(['Filter operator not supported.']);
                    break;
            }
        }

        return $query;
    }

    protected function parseCommaValue($value)
    {
        if (! is_null($value) && ! is_array($value))
        {
            $value = explode(',', $value);
            foreach ($value as &$v) {
                $v = trim($v);
            }
        }

        return $value;
    }

    protected function querySorting($query, $sort)
    {
        if (! empty($sort)) {
            $sort = (is_array($sort))? $sort: [$sort];


            foreach ($sort as $sortCriteria) {
                $sortDirection = 'asc';

                if ($sortCriteria[0] === '-') {
                    $sortCriteria = substr($sortCriteria, 1);
                    $sortDirection = 'desc';
                }

                $query = $this->addSort($query, $sortCriteria, $sortDirection);
            }
        }

        return $query;
    }

    protected function addSort($query, $field, $direction)
    {
        $parentKey = $this->getQueryParentKey($query);

        if(!isset($query[$parentKey]['sort']))
        {
            $query[$parentKey]['sort'] = [];
        }

        $query[$parentKey]['sort'][] = [$field => $direction];

        return $query;
    }

    protected function queryPagination($query, $from, $size)
    {
        if($from + $size > 10000)
        {
            throw new BadRequestException("Can't paginate over 10000 records with elasticsearch, use last_record parapeter instead.");
        }

        $parentKey = $this->getQueryParentKey($query);
        if($size > 0)
        {
            $query[$parentKey]['size'] = $size;
        }
        else
        {
            $size = 10000 - (int)$from;
            $query[$parentKey]['size'] = $size;
        }

        if($from > 0)
        {
            $query[$parentKey]['from'] = $from;
        }

        return $query;
    }

    protected function createEmptyQuery($index)
    {
        return [
            'index' => $index,
            'type' => 'doc',
            'body' => [
                "size" => 10000,
                "query" => [
                    "match_all" => new stdClass()
                ]
            ]
        ];
    }

    protected function createNestedQuery($path)
    {
        return [ 'nested' => [ 'path' => $path, 'query' => [] ] ];
    }

    protected function addNestedQuery($query, $nested)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createFilterQuery($query);

        $query[$parentKey]['query']['bool']['filter'][] = $nested;

        return $query;
    }

    protected function addRangeQuery($query, $field, $filter, $operator, $should = false, $boost = 1)
    {
        $parentKey = $this->getQueryParentKey($query);
        $range = [];
        $range[$field] = [ $operator => $filter, 'boost' => $boost];

        if(!$should)
        {
            $query = $this->createFilterQuery($query);
            $query[$parentKey]['query']['bool']['filter'][] = ['range' => $range];
        }
        else
        {
            $query = $this->createShouldQuery($query);
            $query[$parentKey]['query']['bool']['should'][] = ['range' => $range];
        }
        
        return $query;
    }

    protected function addExistsQuery($query, $field)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createFilterQuery($query);
        $exists = [];
        $exists['field'] = $field;

        $query[$parentKey]['query']['bool']['filter'][] = ['exists' => $exists];
        return $query;
    }

    protected function addMatchQuery($query, $field, $filter)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createMustQuery($query);
        $match = [];
        $match[$field] = $filter;

        $query[$parentKey]['query']['bool']['must'][] = ['match' => $match];
        return $query;
    }

    protected function addMultiMatchQuery($query, $fields, $filter, $type = false, $should = false, $boost = 1)
    {
        $parentKey = $this->getQueryParentKey($query);

        if(!$should)
        {
            $query = $this->createMustQuery($query);
        }
        else
        {
            $query = $this->createShouldQuery($query, 1);
        }
        
        $match = [];
        $match['query'] = $filter;
        $match['fields'] = $fields;
        $match['boost'] = $boost;

        if($type)
        {
            $match['type'] = $type;
        }

        $operator = ($should)?'should':'must';

        $query[$parentKey]['query']['bool'][$operator][] = ['multi_match' => $match];
        return $query;
    }

    protected function addTermQuery($query, $field, $filter)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createFilterQuery($query);
        $term = [];
        $term[$field] = $filter;

        $query[$parentKey]['query']['bool']['filter'][] = ['term' => $term];
        return $query;
    }

    protected function addNotTermQuery($query, $field, $filter)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createMustNotQuery($query);

        $term = [];
        $term[$field] = $filter;

        $query[$parentKey]['query']['bool']['must_not'][] = ['term' => $term];

        return $query;
    }

    protected function addTermsQuery($query, $field, $filter)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createFilterQuery($query);
        $terms = [];
        $terms[$field] = $filter;

        $query[$parentKey]['query']['bool']['filter'][] = ['terms' => $terms];
        return $query;
    }

    protected function addNotTermsQuery($query, $field, $filter)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createMustNotQuery($query);

        $terms = [];
        $terms[$field] = $filter;

        $query[$parentKey]['query']['bool']['must_not'][] = ['terms' => $terms];

        return $query;
    }

    protected function addShouldTermQuery($query, $field, $filter, $minimum_should_match = 1)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createShouldQuery($query, $minimum_should_match);
        $term = [];
        $term[$field] = $filter;

        $query[$parentKey]['query']['bool']['should'][] = ['term' => $term];
        return $query;
    }

    protected function createBoolQuery($query)
    {
        $parentKey = $this->getQueryParentKey($query);

        if(isset($query[$parentKey]['query']['match_all']))
        {
            unset($query[$parentKey]['query']['match_all']);
        }

        if(!isset($query[$parentKey]['query']['bool']))
        {
            $query[$parentKey]['query']['bool'] = [];
        }

        return $query;
    }

    protected function createFilterQuery($query)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createBoolQuery($query);

        if(!isset($query[$parentKey]['query']['bool']['filter']))
        {
            $query[$parentKey]['query']['bool']['filter'] = [];
        }

        return $query;
    }

    protected function createShouldQuery($query, $minimum_should_match = 1)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createBoolQuery($query);

        if(!isset($query[$parentKey]['query']['bool']['should']))
        {
            $query[$parentKey]['query']['bool']['should'] = [];
            $query[$parentKey]['query']['bool']['minimum_should_match'] = $minimum_should_match;
        }

        return $query;
    }

    protected function createMustQuery($query)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createBoolQuery($query);

        if(!isset($query[$parentKey]['query']['bool']['must']))
        {
            $query[$parentKey]['query']['bool']['must'] = [];
        }

        return $query;
    }

    protected function createMustNotQuery($query)
    {
        $parentKey = $this->getQueryParentKey($query);

        $query = $this->createBoolQuery($query);

        if(!isset($query[$parentKey]['query']['bool']['must_not']))
        {
            $query[$parentKey]['query']['bool']['must_not'] = [];
        }

        return $query;
    }

    protected function getQueryParentKey($query)
    {
        $availableParentKeys = ['body', 'nested'];

        foreach ($availableParentKeys as $key)
        {
            if(array_key_exists($key, $query))
            {
                return $key;
            }
        }

        throw new BadRequestException('Bad parent key for elasticsearch query');
    }
}