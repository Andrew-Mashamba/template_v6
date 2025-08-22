<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QueryresponsesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('query_responses')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'message_id' => 1,
                'connector_id' => 1,
                'type' => 'standard',
                'message' => 'Sample message 1',
                'response_data' => json_encode(['status' => 'success', 'data' => 'Sample response 1']),
                'timestamp' => '2025-07-23 10:38:40',
                'CheckNumber' => 000001,
                'created_at' => '2025-07-23 10:38:40',
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'message_id' => 2,
                'connector_id' => 2,
                'type' => 'standard',
                'message' => 'Sample message 2',
                'response_data' => json_encode(['status' => 'success', 'data' => 'Sample response 2']),
                'timestamp' => '2025-07-23 11:38:40',
                'CheckNumber' => 000002,
                'created_at' => '2025-07-23 11:38:40',
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('query_responses')->insert($row);
    }
}
}