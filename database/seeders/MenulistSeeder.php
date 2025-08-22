<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenulistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('menu_list')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'name' => 'Sample name',
                'created_at' => '2025-07-23 10:38:39',
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Sample name',
                'created_at' => '2025-07-23 11:38:39',
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('menu_list')->insert($row);
    }
}
}