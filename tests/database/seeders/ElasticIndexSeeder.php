<?php
/*
 * This file is part of the cookbook/eav package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */ 

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Config;

/**
 * ElasticIndexSeeder
 * 
 * Populates Elastic indices
 * 
 * @uses   		Elasticsearch\ClientBuilder
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	cookbook/eav
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class ElasticIndexSeeder {

	public $client;

	public function __construct($client) {
		$this->client = $client;
	}

	public function up() {

		$prefix = Config::get('cb.elastic.index_prefix');
		
		$params = [
	    	'index' => $prefix . 'entities'
	    ];

	    if($this->client->indices()->exists($params)) {
	    	$this->client->indices()->delete($params);
	    }

		$params = [
	    	'index' => $prefix . 'entities',
	    	'body' => [
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
		    ],
		    
		];

		$this->client->indices()->create($params);


		$params = [
		    'index' => $prefix . 'entities',
		    'type' => 'doc',
		    'id' => 1,
		    'body' => [ 
		    	'id' => 1,
		    	'entity_type_id' => 1,
		    	'attribute_set_id' => 1,
		    	'localized' => true,
		    	'localized_workflow' => true,
		    	'status' => [
		    		[
		    			'status' => 'published',
		    			'locale' => 'en_US',
		    			'state' => 'active',
		    			'scheduled_at' => null,
		    			'created_at' => date("Y-m-d H:i:s"),
		    			'updated_at' => date("Y-m-d H:i:s")
		    		],
		    		[
		    			'status' => 'published',
		    			'locale' => 'fr_FR',
		    			'state' => 'active',
		    			'scheduled_at' => null,
		    			'created_at' => date("Y-m-d H:i:s"),
		    			'updated_at' => date("Y-m-d H:i:s")
		    		],
		    	],
		    	'fields' => [
			    	'attribute1' => 'value1',
			    	'attribute2' => 'value2',
			    	'attribute3__en_US' => 'value3-en',
			    	'attribute3__fr_FR' => 'value3-fr'
			    ],
			    'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
		    ]
		];

		$this->client->index($params);

		$params = [
		    'index' => $prefix . 'entities',
		    'type' => 'doc',
		    'id' => 2,
		    'body' => [ 
		    	'id' => 2,
		    	'entity_type_id' => 2,
		    	'attribute_set_id' => 5,
		    	'localized' => false,
		    	'localized_workflow' => false,
		    	'status' => [
		    		[
		    			'status' => 'public',
		    			'locale' => null,
		    			'state' => 'active',
		    			'scheduled_at' => null,
		    			'created_at' => date("Y-m-d H:i:s"),
		    			'updated_at' => date("Y-m-d H:i:s")
		    		],
		    		[
		    			'status' => 'public',
		    			'locale' => null,
		    			'state' => 'history',
		    			'scheduled_at' => null,
		    			'created_at' => date("Y-m-d H:i:s"),
		    			'updated_at' => date("Y-m-d H:i:s")
		    		],
		    	],
		    	'fields' => [
			    	'attribute1' => 'value12',
			    	'attribute2' => 'value22',
			    ],
			    'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
		    ]
		];

		$this->client->index($params);

		$params = [
		    'index' => $prefix . 'entities',
		    'type' => 'doc',
		    'id' => 3,
		    'body' => [ 
		    	'id' => 3,
		    	'entity_type_id' => 1,
		    	'attribute_set_id' => 1,
		    	'localized' => true,
		    	'localized_workflow' => true,
		    	'status' => [
		    		[
		    			'status' => 'published',
		    			'locale' => 'en_US',
		    			'state' => 'active',
		    			'scheduled_at' => null,
		    			'created_at' => date("Y-m-d H:i:s"),
		    			'updated_at' => date("Y-m-d H:i:s")
		    		],
		    		[
		    			'status' => 'draft',
		    			'locale' => 'fr_FR',
		    			'state' => 'active',
		    			'scheduled_at' => null,
		    			'created_at' => date("Y-m-d H:i:s"),
		    			'updated_at' => date("Y-m-d H:i:s")
		    		],
		    	],
		    	'fields' => [
			    	'attribute1' => 'value12',
			    	'attribute2' => 'value22',
			    	'attribute3__en_US' => 'value3-en',
			    	'attribute3__fr_FR' => 'value3-fr'
			    ],
			    'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
		    ]
		];

		$this->client->index($params);

		$params = [
		    'index' => $prefix . 'entities',
		    'type' => 'doc',
		    'id' => 4,
		    'body' => [ 
		    	'id' => 4,
		    	'entity_type_id' => 1,
		    	'attribute_set_id' => 1,
		    	'localized' => true,
		    	'localized_workflow' => true,
		    	'status' => [
		    		[
		    			'status' => 'published',
		    			'locale' => 'en_US',
		    			'state' => 'active',
		    			'scheduled_at' => null,
		    			'created_at' => date("Y-m-d H:i:s"),
		    			'updated_at' => date("Y-m-d H:i:s")
		    		],
		    		[
		    			'status' => 'published',
		    			'locale' => 'fr_FR',
		    			'state' => 'active',
		    			'scheduled_at' => null,
		    			'created_at' => date("Y-m-d H:i:s"),
		    			'updated_at' => date("Y-m-d H:i:s")
		    		],
		    	],
		    	'fields' => [
			    	'attribute1' => 'abc',
			    	'attribute2' => 'value2',
			    	'attribute3__en_US' => 'value3-en',
			    	'attribute3__fr_FR' => 'value3-fr'
			    ],
			    'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
		    ]
		];

		$this->client->index($params);

		$params = [
		    'index' => $prefix . 'entities',
		    'type' => 'doc',
		    'id' => 5,
		    'body' => [ 
		    	'id' => 5,
		    	'entity_type_id' => 1,
		    	'attribute_set_id' => 1,
		    	'localized' => true,
		    	'localized_workflow' => true,
		    	'status' => [
		    		[
		    			'status' => 'published',
		    			'locale' => 'en_US',
		    			'state' => 'active',
		    			'scheduled_at' => null,
		    			'created_at' => date("Y-m-d H:i:s"),
		    			'updated_at' => date("Y-m-d H:i:s")
		    		],
		    		[
		    			'status' => 'published',
		    			'locale' => 'fr_FR',
		    			'state' => 'active',
		    			'scheduled_at' => null,
		    			'created_at' => date("Y-m-d H:i:s"),
		    			'updated_at' => date("Y-m-d H:i:s")
		    		],
		    	],
		    	'fields' => [
			    	'attribute1' => 'bcd',
			    	'attribute2' => 'value2',
			    	'attribute3__en_US' => 'value3-en',
			    	'attribute3__fr_FR' => 'value3-fr'
			    ],
			    'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
		    ]
		];

		$this->client->index($params);
	}

	public function down() {

		$prefix = Config::get('cb.elastic.index_prefix');

		$params = [
	    	'index' => $prefix . '*'
		];

		$this->client->indices()->delete($params);
	}

}