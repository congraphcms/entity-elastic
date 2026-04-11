<?php
/*
 * This file is part of the congraph/oauth package.
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

/**
 * ClearDB
 * 
 * Clears Database after tests
 * 
 * @uses   		Illuminate\Database\Schema\Blueprint
 * @uses   		Illuminate\Database\Seeder
 * 
 * @author  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @copyright  	Nikola Plavšić <nikolaplavsic@gmail.com>
 * @package 	congraph/oauth
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class ClearDB extends Seeder {

	public function run()
	{
		if (Schema::hasTable('entity_types'))
		{
			reset_table('entity_types');
		}
		if (Schema::hasTable('attributes'))
		{
			reset_table('attributes');
		}
		if (Schema::hasTable('attribute_options'))
		{
			reset_table('attribute_options');
		}
		if (Schema::hasTable('attribute_sets'))
		{
			reset_table('attribute_sets');
		}
		if (Schema::hasTable('set_attributes'))
		{
			reset_table('set_attributes');
		}
		if (Schema::hasTable('entities'))
		{
			reset_table('entities');
		}
		if (Schema::hasTable('entity_statuses'))
		{
			reset_table('entity_statuses');
		}
		if (Schema::hasTable('attribute_values_varchar'))
		{
			reset_table('attribute_values_varchar');
		}
		if (Schema::hasTable('attribute_values_fulltext'))
		{
			reset_table('attribute_values_fulltext');
		}
		if (Schema::hasTable('attribute_values_text'))
		{
			reset_table('attribute_values_text');
		}
		if (Schema::hasTable('attribute_values_integer'))
		{
			reset_table('attribute_values_integer');
		}
		if (Schema::hasTable('attribute_values_decimal'))
		{
			reset_table('attribute_values_decimal');
		}
		if (Schema::hasTable('attribute_values_datetime'))
		{
			reset_table('attribute_values_datetime');
		}
		if (Schema::hasTable('files'))
		{
			reset_table('files');
		}
		if (Schema::hasTable('locales'))
		{
			reset_table('locales');
		}
		if (Schema::hasTable('workflows'))
		{
			reset_table('workflows');
		}
		if (Schema::hasTable('workflow_points'))
		{
			reset_table('workflow_points');
		}
		if (Schema::hasTable('workflow_steps'))
		{
			reset_table('workflow_steps');
		}
	}

}
