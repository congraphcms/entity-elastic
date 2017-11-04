<?php
/*
 * This file is part of the cookbook/entity-elastic package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


return array(

	'hosts' => [
	    // This is effectively equal to: "https://username:password!#$?*abc@foo.com:9200/"
	    [
	        'host' => env('ELASTICSEARCH_HOST', 'localhost'),
	        'port' => env('ELASTICSEARCH_PORT', '9200'),
	        'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),
	        'user' => env('ELASTICSEARCH_USER', 'elastic'),
	        'pass' => env('ELASTICSEARCH_PASSWORD', 'changeme')
	    ]
	],

	'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'congraph_'),

	'default_index_mappings' => [
		'settings' => [
            "index.mapping.single_type" => true
        ],
        'mappings' => [
            'doc' => [
                'properties' => [
                    'entity_type_id' => [
                        'type' => 'integer'
                    ],
                    'attribute_set_id' => [
                        'type' => 'integer'
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
                                "type" => "date",
                                "format" => "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
                            ],
                            'updated_at' => [
                                "type" => "date",
                                "format" => "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
                            ]
                        ]
                    ],
                    'created_at' => [
                        "type" => "date",
                        "format" => "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
                    ],
                    'updated_at' => [
                        "type" => "date",
                        "format" => "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
                    ],
                    'fields' => [
                        'type' => 'object'
                    ],
                ]
            ]
        ]
    ]

);