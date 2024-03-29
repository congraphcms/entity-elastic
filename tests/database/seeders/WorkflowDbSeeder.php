<?php
/*
 * This file is part of the congraph/eav package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Database\Seeders;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
/**
 * WorkflowDbSeeder
 * 
 * Populates DB with data for testing
 * 
 * @uses   		Illuminate\Database\Schema\Blueprint
 * @uses   		Illuminate\Database\Seeder
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/eav
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class WorkflowDbSeeder extends Seeder {

	public function run()
	{
		DB::table('workflows')->truncate();
		DB::table('workflows')->insert([
			[
				'name' => 'Default',
				'description' => 'Only one public state',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'name' => 'Basic Publishing',
				'description' => 'Testing workflows',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			]
		]);

		DB::table('workflow_points')->truncate();
		DB::table('workflow_points')->insert([
			[
				'workflow_id' => 1,
				'status' => 'public',
				'endpoint' => 'publish',
				'action' => 'Publish',
				'name' => 'Public',
				'description' => 'Public',
				'public' => 1,
				'deleted' => 0,
				'sort_order' => 0,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'workflow_id' => 2,
				'status' => 'trashed',
				'endpoint' => 'trash',
				'action' => 'Trash',
				'name' => 'Trashed',
				'description' => 'Trashed objects',
				'public' => 0,
				'deleted' => 1,
				'sort_order' => 0,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'workflow_id' => 2,
				'status' => 'draft',
				'endpoint' => 'move_to_drafts',
				'action' => 'Move to drafts',
				'name' => 'Draft',
				'description' => 'Draft objects',
				'public' => 0,
				'deleted' => 0,
				'sort_order' => 1,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'workflow_id' => 2,
				'status' => 'published',
				'endpoint' => 'publish',
				'action' => 'Publish',
				'name' => 'Published',
				'description' => 'Published objects',
				'public' => 1,
				'deleted' => 0,
				'sort_order' => 2,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
		]);

		DB::table('workflow_steps')->truncate();
		DB::table('workflow_steps')->insert([
			[
				'workflow_id' => 2,
				'from_id' => 2,
				'to_id' => 3,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'workflow_id' => 2,
				'from_id' => 3,
				'to_id' => 2,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'workflow_id' => 2,
				'from_id' => 3,
				'to_id' => 4,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			],
			[
				'workflow_id' => 2,
				'from_id' => 4,
				'to_id' => 3,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s")
			]
		]);
	}
}