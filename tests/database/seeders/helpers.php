<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

function reset_table(string $table): void
{
	$driver = DB::connection()->getDriverName();

	if ($driver === 'pgsql') {
		DB::statement(sprintf('TRUNCATE TABLE "%s" RESTART IDENTITY CASCADE', $table));

		return;
	}

	if ($driver === 'mysql') {
		DB::statement('SET FOREIGN_KEY_CHECKS=0');
		DB::table($table)->truncate();
		DB::statement('SET FOREIGN_KEY_CHECKS=1');

		return;
	}

	DB::table($table)->delete();
	DB::statement("DELETE FROM sqlite_sequence WHERE name = '{$table}'");
}
