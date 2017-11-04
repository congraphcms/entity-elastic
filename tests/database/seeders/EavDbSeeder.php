<?php
/*
 * This file is part of the cookbook/eav package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */ 

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
/**
 * TestDbSeeder
 * 
 * Populates DB with data for testing
 * 
 * @uses   		Illuminate\Database\Schema\Blueprint
 * @uses   		Illuminate\Database\Seeder
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	cookbook/eav
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class EavDbSeeder extends Seeder {

	public function run()
	{
		DB::table('entity_types')->truncate();

		DB::table('entity_types')->insert([
			[
				'code' => 'cg_test_1',
				'endpoint' => 'cg_test_1',
				'name' => 'Test',
				'plural_name' => 'Tests',
				'multiple_sets' => 1,
				'localized' => 1,
				'workflow_id' => 2,
				'default_point_id' => 3,
				'localized_workflow' => 1,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'cg_test_2',
				'endpoint' => 'cg_test_2',
				'name' => 'Test2',
				'plural_name' => 'Tests2',
				'multiple_sets' => 0,
				'localized' => 0,
				'workflow_id' => 1,
				'default_point_id' => 1,
				'localized_workflow' => 0,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'cg_test_3',
				'endpoint' => 'cg_test_3',
				'name' => 'Test3',
				'plural_name' => 'Tests3',
				'multiple_sets' => 1,
				'localized' => 0,
				'workflow_id' => 1,
				'default_point_id' => 1,
				'localized_workflow' => 0,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'cg_test_fields',
				'endpoint' => 'cg_test_fields',
				'name' => 'Test Field',
				'plural_name' => 'Test Fields',
				'multiple_sets' => 1,
				'localized' => 0,
				'workflow_id' => 1,
				'default_point_id' => 1,
				'localized_workflow' => 0,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
		]);

		DB::table('attributes')->truncate();

		DB::table('attributes')->insert([
			[
				'code' => 'attribute1',
				'field_type' => 'text',
				'table' => 'attribute_values_text',
				'localized' => false,
				'default_value' => '',
				'unique' => false,
				'required' => true,
				'filterable' => false,
				'status' => 'system_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'attribute2',
				'field_type' => 'text',
				'table' => 'attribute_values_text',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => false,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'attribute3',
				'field_type' => 'text',
				'table' => 'attribute_values_text',
				'localized' => true,
				'default_value' => null,
				'unique' => true,
				'required' => false,
				'filterable' => false,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'attribute4',
				'field_type' => 'text',
				'table' => 'attribute_values_text',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'attribute5',
				'field_type' => 'text',
				'table' => 'attribute_values_text',
				'localized' => false,
				'default_value' => '123',
				'unique' => false,
				'required' => true,
				'filterable' => false,
				'status' => 'system_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'attribute6',
				'field_type' => 'text',
				'table' => 'attribute_values_text',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => false,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'attribute7',
				'field_type' => 'text',
				'table' => 'attribute_values_text',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'system_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_text_attribute',
				'field_type' => 'text',
				'table' => 'attribute_values_text',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			
			[
				'code' => 'test_select_attribute',
				'field_type' => 'select',
				'table' => 'attribute_values_integer',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_integer_attribute',
				'field_type' => 'integer',
				'table' => 'attribute_values_integer',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],

			[
				'code' => 'test_decimal_attribute',
				'field_type' => 'decimal',
				'table' => 'attribute_values_decimal',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_datetime_attribute',
				'field_type' => 'datetime',
				'table' => 'attribute_values_datetime',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_relation_attribute',
				'field_type' => 'relation',
				'table' => 'attribute_values_integer',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_asset_attribute',
				'field_type' => 'asset',
				'table' => 'attribute_values_integer',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_boolean_attribute',
				'field_type' => 'boolean',
				'table' => 'attribute_values_integer',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_location_attribute',
				'field_type' => 'location',
				'table' => 'attribute_values_text',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => false,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_tags_attribute',
				'field_type' => 'tags',
				'table' => 'attribute_values_text',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_multiselect_attribute',
				'field_type' => 'multiselect',
				'table' => 'attribute_values_integer',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_assetcollection_attribute',
				'field_type' => 'asset_collection',
				'table' => 'attribute_values_integer',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],

			[
				'code' => 'test_relationcollection_attribute',
				'field_type' => 'relation_collection',
				'table' => 'attribute_values_integer',
				'localized' => false,
				'default_value' => null,
				'unique' => false,
				'required' => false,
				'filterable' => true,
				'status' => 'user_defined',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			
		]);

		DB::table('attribute_options')->truncate();

		DB::table('attribute_options')->insert([
			[
				'value' => 'option1',
				'label' => 'Option 1',
				'attribute_id' => 9,
				'default' => 0,
				'locale' => 0,
				'sort_order' => 0
			],
			[
				'value' => 'option2',
				'label' => 'Option 2',
				'attribute_id' => 9,
				'default' => 1,
				'locale' => 0,
				'sort_order' => 1
			],
			[
				'value' => 'option3',
				'label' => 'Option 3',
				'attribute_id' => 9,
				'default' => 0,
				'locale' => 0,
				'sort_order' => 2
			],
			[
				'value' => 'option1',
				'label' => 'Option 1',
				'attribute_id' => 18,
				'default' => 0,
				'locale' => 0,
				'sort_order' => 0
			],
			[
				'value' => 'option2',
				'label' => 'Option 2',
				'attribute_id' => 18,
				'default' => 1,
				'locale' => 0,
				'sort_order' => 1
			],
			[
				'value' => 'option3',
				'label' => 'Option 3',
				'attribute_id' => 18,
				'default' => 0,
				'locale' => 0,
				'sort_order' => 2
			],
		]);

		DB::table('attribute_sets')->truncate();
		DB::table('set_attributes')->truncate();

		DB::table('attribute_sets')->insert([
			[
				'code' => 'attribute_set1',
				'name' => 'Attribute Set 1',
				'entity_type_id' => 1,
				'primary_attribute_id' => 1,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'attribute_set2',
				'name' => 'Attribute Set 2',
				'entity_type_id' => 1,
				'primary_attribute_id' => 3,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'attribute_set3',
				'name' => 'Attribute Set 3',
				'entity_type_id' => 1,
				'primary_attribute_id' => 5,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'test_fields_set',
				'name' => 'Test Fields Set',
				'entity_type_id' => 4,
				'primary_attribute_id' => 8,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'code' => 'attribute_set5',
				'name' => 'Attribute Set 5',
				'entity_type_id' => 2,
				'primary_attribute_id' => 1,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
		]);

		DB::table('set_attributes')->insert([
			[
				'attribute_set_id' => 1,
				'attribute_id' => 1,
				'sort_order' => 1
			],
			[
				'attribute_set_id' => 1,
				'attribute_id' => 2,
				'sort_order' => 0
			],
			[
				'attribute_set_id' => 1,
				'attribute_id' => 3,
				'sort_order' => 2
			],
			[
				'attribute_set_id' => 2,
				'attribute_id' => 3,
				'sort_order' => 0
			],
			[
				'attribute_set_id' => 2,
				'attribute_id' => 4,
				'sort_order' => 1
			],
			[
				'attribute_set_id' => 3,
				'attribute_id' => 5,
				'sort_order' => 3
			],
			[
				'attribute_set_id' => 3,
				'attribute_id' => 6,
				'sort_order' => 2
			],
			[
				'attribute_set_id' => 3,
				'attribute_id' => 7,
				'sort_order' => 1
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 8,
				'sort_order' => 0
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 9,
				'sort_order' => 1
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 10,
				'sort_order' => 2
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 11,
				'sort_order' => 3
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 12,
				'sort_order' => 4
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 13,
				'sort_order' => 5
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 14,
				'sort_order' => 6
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 15,
				'sort_order' => 7
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 16,
				'sort_order' => 8
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 17,
				'sort_order' => 9
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 18,
				'sort_order' => 10
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 19,
				'sort_order' => 11
			],
			[
				'attribute_set_id' => 4,
				'attribute_id' => 20,
				'sort_order' => 12
			],
			[
				'attribute_set_id' => 5,
				'attribute_id' => 1,
				'sort_order' => 1
			],
			[
				'attribute_set_id' => 5,
				'attribute_id' => 2,
				'sort_order' => 0
			]
		]);

		DB::table('entities')->truncate();
		DB::table('entities')->insert([
			[
				'entity_type_id' => 1,
				'attribute_set_id' => 1,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'entity_type_id' => 1,
				'attribute_set_id' => 1,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'entity_type_id' => 1,
				'attribute_set_id' => 2,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			]
		]);

		DB::table('entity_statuses')->truncate();
		DB::table('entity_statuses')->insert([
			[
				'entity_id' => 1,
				'workflow_point_id' => 4,
				'locale_id' => 1,
				'state' => 'active',
				'scheduled_at' => null,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'entity_id' => 1,
				'workflow_point_id' => 4,
				'locale_id' => 2,
				'state' => 'active',
				'scheduled_at' => null,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'entity_id' => 2,
				'workflow_point_id' => 2,
				'locale_id' => 1,
				'state' => 'active',
				'scheduled_at' => null,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'entity_id' => 2,
				'workflow_point_id' => 2,
				'locale_id' => 2,
				'state' => 'active',
				'scheduled_at' => null,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'entity_id' => 3,
				'workflow_point_id' => 4,
				'locale_id' => 1,
				'state' => 'active',
				'scheduled_at' => null,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'entity_id' => 3,
				'workflow_point_id' => 4,
				'locale_id' => 2,
				'state' => 'active',
				'scheduled_at' => null,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'entity_id' => 4,
				'workflow_point_id' => 1,
				'locale_id' => 0,
				'state' => 'active',
				'scheduled_at' => null,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			]
		]);

		// DB::table('attribute_values_varchar')->truncate();
		// DB::table('attribute_values_varchar')->insert([
		// 	[
		// 		'entity_id' => 1,
		// 		'entity_type_id' => 1,
		// 		'attribute_set_id' => 1,
		// 		'attribute_id' => 1,
		// 		'locale_id' => 0,
		// 		'sort_order' => 0,
		// 		'value' => 'value1',
		// 	],
		// 	[
		// 		'entity_id' => 1,
		// 		'entity_type_id' => 1,
		// 		'attribute_set_id' => 1,
		// 		'attribute_id' => 2,
		// 		'locale_id' => 0,
		// 		'sort_order' => 0,
		// 		'value' => 'value2',
		// 	],
		// 	[
		// 		'entity_id' => 1,
		// 		'entity_type_id' => 1,
		// 		'attribute_set_id' => 1,
		// 		'attribute_id' => 3,
		// 		'locale_id' => 1,
		// 		'sort_order' => 0,
		// 		'value' => 'value3-en',
		// 	],
		// 	[
		// 		'entity_id' => 1,
		// 		'entity_type_id' => 1,
		// 		'attribute_set_id' => 1,
		// 		'attribute_id' => 3,
		// 		'locale_id' => 2,
		// 		'sort_order' => 0,
		// 		'value' => 'value3-fr',
		// 	],
		// 	[
		// 		'entity_id' => 2,
		// 		'entity_type_id' => 1,
		// 		'attribute_set_id' => 1,
		// 		'attribute_id' => 1,
		// 		'locale_id' => 0,
		// 		'sort_order' => 0,
		// 		'value' => 'value12',
		// 	],
		// 	[
		// 		'entity_id' => 2,
		// 		'entity_type_id' => 1,
		// 		'attribute_set_id' => 1,
		// 		'attribute_id' => 2,
		// 		'locale_id' => 0,
		// 		'sort_order' => 0,
		// 		'value' => 'value22',
		// 	],
		// 	[
		// 		'entity_id' => 2,
		// 		'entity_type_id' => 1,
		// 		'attribute_set_id' => 1,
		// 		'attribute_id' => 3,
		// 		'locale_id' => 0,
		// 		'sort_order' => 0,
		// 		'value' => 'value32',
		// 	],
		// 	[
		// 		'entity_id' => 3,
		// 		'entity_type_id' => 1,
		// 		'attribute_set_id' => 2,
		// 		'attribute_id' => 3,
		// 		'locale_id' => 0,
		// 		'sort_order' => 0,
		// 		'value' => 'value3',
		// 	],
		// 	[
		// 		'entity_id' => 3,
		// 		'entity_type_id' => 1,
		// 		'attribute_set_id' => 2,
		// 		'attribute_id' => 4,
		// 		'locale_id' => 0,
		// 		'sort_order' => 0,
		// 		'value' => 'value4',
		// 	],
		// 	[
		// 		'entity_id' => 4,
		// 		'entity_type_id' => 4,
		// 		'attribute_set_id' => 4,
		// 		'attribute_id' => 8,
		// 		'locale_id' => 0,
		// 		'sort_order' => 0,
		// 		'value' => 'field text value',
		// 	]
		// ]);
		
		DB::table('attribute_values_text')->truncate();
		DB::table('attribute_values_text')->insert([
			[
				'entity_id' => 1,
				'entity_type_id' => 1,
				'attribute_set_id' => 1,
				'attribute_id' => 1,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 'value1',
			],
			[
				'entity_id' => 1,
				'entity_type_id' => 1,
				'attribute_set_id' => 1,
				'attribute_id' => 2,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 'value2',
			],
			[
				'entity_id' => 1,
				'entity_type_id' => 1,
				'attribute_set_id' => 1,
				'attribute_id' => 3,
				'locale_id' => 1,
				'sort_order' => 0,
				'value' => 'value3-en',
			],
			[
				'entity_id' => 1,
				'entity_type_id' => 1,
				'attribute_set_id' => 1,
				'attribute_id' => 3,
				'locale_id' => 2,
				'sort_order' => 0,
				'value' => 'value3-fr',
			],
			[
				'entity_id' => 2,
				'entity_type_id' => 1,
				'attribute_set_id' => 1,
				'attribute_id' => 1,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 'value12',
			],
			[
				'entity_id' => 2,
				'entity_type_id' => 1,
				'attribute_set_id' => 1,
				'attribute_id' => 2,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 'value22',
			],
			[
				'entity_id' => 2,
				'entity_type_id' => 1,
				'attribute_set_id' => 1,
				'attribute_id' => 3,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 'value32',
			],
			[
				'entity_id' => 3,
				'entity_type_id' => 1,
				'attribute_set_id' => 2,
				'attribute_id' => 3,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 'value3',
			],
			[
				'entity_id' => 3,
				'entity_type_id' => 1,
				'attribute_set_id' => 2,
				'attribute_id' => 4,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 'value4',
			],
			[
				'entity_id' => 4,
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'attribute_id' => 8,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 'field text value',
			],
			[
				'entity_id' => 4,
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'attribute_id' => 16,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => '{"lat":12,"lng":35}',
			]
		]);

		DB::table('attribute_values_integer')->truncate();
		DB::table('attribute_values_integer')->insert([
			[
				'entity_id' => 4,
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'attribute_id' => 9,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 1,
			],
			[
				'entity_id' => 4,
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'attribute_id' => 10,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 11,
			],
			[
				'entity_id' => 4,
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'attribute_id' => 14,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 1,
			],
			[
				'entity_id' => 4,
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'attribute_id' => 13,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 1,
			],
			[
				'entity_id' => 4,
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'attribute_id' => 15,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 0,
			]
		]);

		DB::table('attribute_values_decimal')->truncate();
		DB::table('attribute_values_decimal')->insert([
			[
				'entity_id' => 4,
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'attribute_id' => 11,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => 11.1,
			]
		]);

		DB::table('attribute_values_datetime')->truncate();
		DB::table('attribute_values_datetime')->insert([
			[
				'entity_id' => 4,
				'entity_type_id' => 4,
				'attribute_set_id' => 4,
				'attribute_id' => 12,
				'locale_id' => 0,
				'sort_order' => 0,
				'value' => Carbon::now()->toDateTimeString(),
			]
		]);

		
	}

}