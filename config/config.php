<?php
/*
 * This file is part of the congraph/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


return array(

	'hosts' => explode(',', env('ES_HOSTS', 'localhost')),
    'apiId' => env('ES_API_ID', ''),
    'apiKey' => env('ES_API_Key', ''),

	'index_prefix' => env('ES_INDEX_PREFIX', 'congraph_'),

    'use_date_relevance' => env('ES_USE_DATE_RELEVANCE', true),

    // how often should the difference be made (in days)
    'date_relevance_interval' => env('ES_DATE_RELEVANCE_INTERVAL', 30),

    // how long in the past should relevance reach (count * interval)
    'date_relevance_interval_count' => env('ES_DATE_RELEVANCE_INTERVAL_COUNT', 6),

    // how much boost should each interval have
    'date_relevance_boost_step' => env('ES_DATE_RELEVANCE_BOOST_STEP', 5),

	'default_index_mappings' => [
		'settings' => [
            // "index.mapping.single_type" => true,
            "analysis" => [
                "analyzer" => [
                    "default" => [
                        "tokenizer" => "standard",
                        "filter" => ["my_ascii_folding", "lowercase"],
                        "char_filter" => [
                          "small_dj",
                          "big_dj"
                        ]
                    ]
                ],
                "filter" => [
                    "my_ascii_folding" => [
                        "type" => "asciifolding",
                        "preserve_original" => true
                    ]
                ],
                "char_filter" => [
                  "small_dj" => [
                    "type" => "pattern_replace",
                    "pattern" => "(\\S*)(đ)(\\S*)",
                    "replacement" => "$0 $1dj$3"
                  ],
                  "big_dj" => [
                    "type" => "pattern_replace",
                    "pattern" => "(\\S*)(Đ)(\\S*)",
                    "replacement" => "$0 $1Dj$3"
                  ]
                ]
            ]
        ],
        'mappings' => [
            'properties' => [
                'entity_type_id' => [
                    'type' => 'long'
                ],
                'attribute_set_id' => [
                    'type' => 'long'
                ],
                'localized' => [
                    'type' => 'boolean'
                ],
                'localized_workflow' => [
                    'type' => 'boolean'
                ],
                'status' => [
                    'type' => 'nested',
                    'properties' => [
                        'locale' => [
                            'type' => 'keyword'
                        ],
                        'status' => [
                            'type' => 'keyword'
                        ],
                        'state' => [
                            'type' => 'keyword'
                        ],
                        'created_at' => [
                            "type" => "long"
                        ],
                        'updated_at' => [
                            "type" => "long"
                        ]
                    ]
                ],
                'created_at' => [
                    "type" => "long"
                ],
                'updated_at' => [
                    "type" => "long"
                ],
                'fields' => [
                    'type' => 'object'
                ],
            ]
        ]
    ]

);