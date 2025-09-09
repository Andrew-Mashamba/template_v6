<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiinteractionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('ai_interactions')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'session_id' => 1,
                'query' => 'Sample query 1',
                'response' => 'Sample response 1',
                'context' => json_encode(['previous_queries' => [], 'session_context' => 'Initial query']),
                'metadata' => json_encode(['model' => 'gpt-4', 'tokens' => 150, 'duration_ms' => 1200]),
                'created_at' => '2025-07-23 10:38:32',
                'updated_at' => now(),
                'user_id' => 1,
            ],
            [
                'id' => 2,
                'session_id' => 2,
                'query' => 'Sample query 2',
                'response' => 'Sample response 2',
                'context' => json_encode(['previous_queries' => ['Sample query 1'], 'session_context' => 'Follow-up query']),
                'metadata' => json_encode(['model' => 'gpt-4', 'tokens' => 200, 'duration_ms' => 1500]),
                'created_at' => '2025-07-23 11:38:32',
                'updated_at' => now(),
                'user_id' => 2,
            ],
        ];

        foreach ($data as $row) {
            // Validate user_id (required field)
            if (isset($row['user_id'])) {
                $userExists = DB::table('users')->where('id', $row['user_id'])->exists();
                if (!$userExists) {
                    $firstUser = DB::table('users')->first();
                    if (!$firstUser) {
                        // Skip this record if no users exist
                        if ($this->command) $this->command->warn("Skipping ai_interaction - no users found");
                        continue;
                    }
                    $row['user_id'] = $firstUser->id;
                }
            }
            
            DB::table('ai_interactions')->insert($row);
        }
}
}