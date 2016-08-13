<?php

use Illuminate\Database\Seeder;
use \DB as DB;

class CluesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csv = storage_path().'/app/seeds/clues-data.csv';
		$query = sprintf("
			LOAD DATA local INFILE '%s' 
			INTO TABLE clues 
			FIELDS TERMINATED BY ',' 
			OPTIONALLY ENCLOSED BY '\"' 
			ESCAPED BY '\"' 
			LINES TERMINATED BY '\\n' 
			IGNORE 1 LINES", addslashes($csv));
		DB::connection()->getpdo()->exec($query);
    }
}
