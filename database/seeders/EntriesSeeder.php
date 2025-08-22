<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('entries')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'content' => 'Sample content 1',
                'created_at' => '2025-07-23 10:38:35',
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'content' => 'Sample content 2',
                'created_at' => '2025-07-23 11:38:35',
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('entries')->insert($row);
    }
}
}