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

    /**
     * You can specify one of several different connections when building an
     * Elasticsearch client.
     *
     * Here you may specify which of the connections below you wish to use
     * as your default connection when building an client. Of course you may
     * use create several clients at once, each with different configurations.
     */

    'default_connection' => env('ES_DEFAULT_CONNECTION', 'local'),

    /**
     * These are the connection parameters used when building a client.
     */

    'connections' => [

        'local' => [
            'hosts' => explode(',', env('ES_HOSTS', 'localhost')),
        ],

        'bonsai' => [
            'hosts' => explode(',', env('ES_BONSAI_HOSTS', 'localhost')),
        ],

        'elastic' => [
            'host' => env('ES_ELASTIC_HOST', 'localhost'),
            'api_id' => env('ES_ELASTIC_API_ID', ''),
            'api_key' => env('ES_ELASTIC_API_KEY', ''),
        ]
    ],

    /**
     * Logging
     *
     * Logging is handled by passing in an instance of Monolog\Logger (which
     * coincidentally is what Laravel's default logger is).
     *
     * If logging is enabled, you either need to set the path and log level
     * (some defaults are given for you below), or you can use a custom logger by
     * setting 'logObject' to an instance of Psr\Log\LoggerInterface.  In fact,
     * if you just want to use the default Laravel logger, then set 'logObject'
     * to \Log::getMonolog().
     *
     * Note: 'logObject' takes precedent over 'logPath'/'logLevel', so set
     * 'logObject' null if you just want file-based logging to a custom path.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#enabling_logger
     */

    'logging' => env('ES_LOGGING', true),

    // If you have an existing instance of Monolog you can use it here.
    // 'logObject' => \Log::getMonolog(),

    'logPath' => storage_path('logs/elasticsearch.log'),

    'logLevel' => env('ES_LOGGING_LEVEL', \Monolog\Logger::DEBUG),




	'index_prefix' => env('ES_INDEX_PREFIX', 'congraph_'),

    'use_date_relevance' => env('ES_USE_DATE_RELEVANCE', false),

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
                        "type" => "custom",
                        "tokenizer" => "standard",
                        "filter" => ["my_ascii_folding", "lowercase"],
                        "char_filter" => ["small_rs_dj", "big_rs_dj"]
                    ]
                ],
                "filter" => [
                    "my_ascii_folding" => [
                        "type" => "asciifolding",
                        "preserve_original" => true
                    ]
                ],
                "char_filter" => [
                    "small_rs_dj" => [
                        "type" => "mapping",
                        "mappings" => [
                            "dj => đ"
                        ]
                    ],
                    "big_rs_dj" => [
                        "type" => "mapping",
                        "mappings" => [
                            "Dj => Đ"
                        ]
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
                    'type' => 'object',
                    'properties' => [
                        // 'attribute1__en_US' => [
                        //     'type' => 'text'
                        // ],
                        // 'attribute2__en_US' => [
                        //     'type' => 'text'
                        // ],
                        // 'attribute3__en_US' => [
                        //     'type' => 'text'
                        // ],
                        // 'attribute1__fr_FR' => [
                        //     'type' => 'text'
                        // ],
                        // 'attribute2__fr_FR' => [
                        //     'type' => 'text'
                        // ],
                        // 'attribute3__fr_FR' => [
                        //     'type' => 'text'
                        // ],
                    ]
                ],
            ]
        ]
    ]

);