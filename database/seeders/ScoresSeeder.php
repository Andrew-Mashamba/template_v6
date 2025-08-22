<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('scores')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'client_id' => 1,
                'date' => '2025-07-24',
                'grade' => 'Sample grade 1',
                'score' => 80,
                'trend' => 'Sample trend 1',
                'reasons' => json_encode(['reason1' => 'High payment history', 'reason2' => 'Good credit utilization']),
                'probability_of_default' => 0.05,
                'created_at' => '2025-07-23 10:38:41',
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'client_id' => 2,
                'date' => '2025-07-25',
                'grade' => 'Sample grade 2',
                'score' => 80,
                'trend' => 'Sample trend 2',
                'reasons' => json_encode(['reason1' => 'Recent late payments', 'reason2' => 'High debt ratio']),
                'probability_of_default' => 0.15,
                'created_at' => '2025-07-23 11:38:41',
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('scores')->insert($row);
    }
}
}