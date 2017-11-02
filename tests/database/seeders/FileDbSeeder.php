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
class FileDbSeeder extends Seeder {

	public function run()
	{
		DB::table('files')->truncate();
		DB::table('files')->insert([
			[
				'url' => 'files/test.jpg',
				'name' => 'test.jpg',
				'extension' => 'jpg',
				'mime_type' => 'image/jpeg',
				'size' => 149473,
				'caption' => 'test file',
				'description' => 'test description',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'url' => 'files/test2.jpg',
				'name' => 'test2.jpg',
				'extension' => 'jpg',
				'mime_type' => 'image/jpeg',
				'size' => 149473,
				'caption' => 'test file 2',
				'description' => 'test description 2',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			]
		]);

		// DB::table('attribute_values_integer')->insert([
		// 	[
		// 		'entity_id' => 4,
		// 		'entity_type_id' => 4,
		// 		'attribute_set_id' => 4,
		// 		'attribute_id' => 15,
		// 		'locale_id' => 0,
		// 		'sort_order' => 0,
		// 		'value' => 1,
		// 	]
		// ]);
	}
}