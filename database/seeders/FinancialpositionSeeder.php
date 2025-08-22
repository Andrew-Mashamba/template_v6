<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinancialpositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('financial_position')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'end_of_business_year' => date('Y-12-31'),
                'created_at' => '2025-07-23 10:38:36',
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'end_of_business_year' => date('Y-12-31'),
                'created_at' => '2025-07-23 11:38:36',
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('financial_position')->insert($row);
    }
}
}