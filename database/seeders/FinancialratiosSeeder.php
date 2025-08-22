<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinancialratiosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('financial_ratios')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'end_of_financial_year_date' => '2024-12-31',
                'core_capital' => 1000000.00,
                'total_assets' => 5000000.00,
                'net_capital' => 800000.00,
                'short_term_assets' => 1500000.00,
                'short_term_liabilities' => 500000.00,
                'expenses' => 200000.00,
                'income' => 300000.00,
                'created_at' => '2025-07-23 10:38:36',
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'end_of_financial_year_date' => '2025-12-31',
                'core_capital' => 1200000.00,
                'total_assets' => 6000000.00,
                'net_capital' => 1000000.00,
                'short_term_assets' => 2000000.00,
                'short_term_liabilities' => 600000.00,
                'expenses' => 250000.00,
                'income' => 400000.00,
                'created_at' => '2025-07-23 11:38:36',
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('financial_ratios')->insert($row);
    }
}
}