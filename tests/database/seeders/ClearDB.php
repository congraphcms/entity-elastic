<?php
/*
 * This file is part of the cookbook/oauth package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
 * @package 	cookbook/oauth
 * @since 		0.1.0-alpha
 * @version  	0.1.0-alpha
 */
class ClearDB extends Seeder {

	public function run()
	{
		if (Schema::hasTable('entity_types'))
		{
			DB::table('entity_types')->truncate();
		}
		if (Schema::hasTable('attributes'))
		{
			DB::table('attributes')->truncate();
		}
		if (Schema::hasTable('attribute_options'))
		{
			DB::table('attribute_options')->truncate();
		}
		if (Schema::hasTable('attribute_sets'))
		{
			DB::table('attribute_sets')->truncate();
		}
		if (Schema::hasTable('set_attributes'))
		{
			DB::table('set_attributes')->truncate();
		}
		if (Schema::hasTable('entities'))
		{
			DB::table('entities')->truncate();
		}
		if (Schema::hasTable('entity_statuses'))
		{
			DB::table('entity_statuses')->truncate();
		}
		if (Schema::hasTable('attribute_values_varchar'))
		{
			DB::table('attribute_values_varchar')->truncate();
		}
		if (Schema::hasTable('attribute_values_fulltext'))
		{
			DB::table('attribute_values_fulltext')->truncate();
		}
		if (Schema::hasTable('attribute_values_text'))
		{
			DB::table('attribute_values_text')->truncate();
		}
		if (Schema::hasTable('attribute_values_integer'))
		{
			DB::table('attribute_values_integer')->truncate();
		}
		if (Schema::hasTable('attribute_values_decimal'))
		{
			DB::table('attribute_values_decimal')->truncate();
		}
		if (Schema::hasTable('attribute_values_datetime'))
		{
			DB::table('attribute_values_datetime')->truncate();
		}
		if (Schema::hasTable('files'))
		{
			DB::table('files')->truncate();
		}
		if (Schema::hasTable('locales'))
		{
			DB::table('locales')->truncate();
		}
		if (Schema::hasTable('workflows'))
		{
			DB::table('workflows')->truncate();
		}
		if (Schema::hasTable('workflow_points'))
		{
			DB::table('workflow_points')->truncate();
		}
		if (Schema::hasTable('workflow_steps'))
		{
			DB::table('workflow_steps')->truncate();
		}
	}

}