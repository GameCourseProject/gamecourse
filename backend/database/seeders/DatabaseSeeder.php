<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Loop through all seeders and run them
        // To exclude a seeder put '.' in the beginning of the filename

        $files_arr = scandir( dirname(__FILE__) );

        foreach ($files_arr as $key => $file){
            if ($file !== 'DatabaseSeeder.php' && $file[0] !== "."){
                $this->call( 'Database\\Seeders\\' . explode('.', $file)[0] );
            }
        }
    }
}
