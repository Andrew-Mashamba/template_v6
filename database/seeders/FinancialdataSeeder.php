<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinancialdataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('financial_data')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'description' => 'This is a sample description for financial_data record 1',
                'category' => 'Sample category 1',
                'value' => 1000.00,
                'end_of_business_year' => date('Y-12-31'),
                'unit' => 'Sample unit 1',
            ],
            [
                'id' => 2,
                'description' => 'This is a sample description for financial_data record 2',
                'category' => 'Sample category 2',
                'value' => 1000.00,
                'end_of_business_year' => date('Y-12-31'),
                'unit' => 'Sample unit 2',
            ],
        ];

        foreach ($data as $row) {
            DB::table('financial_data')->insert($row);
    }
}
}