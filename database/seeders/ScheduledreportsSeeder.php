<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduledreportsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('scheduled_reports')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'report_type' => 'TYPE_B',
                'report_config' => json_encode(['value' => 'Sample report_config 1']),
                'user_id' => 1,
                'status' => 'scheduled',
                'frequency' => 'daily',
                'scheduled_at' => now(),
                'last_run_at' => null,
                'next_run_at' => '2025-07-24 10:00:00',
                'error_message' => 'Sample error_message 1',
                'output_path' => 'Sample output_path 1',
                'email_recipients' => 'sample1@scheduled_reports.com',
                'email_sent' => true,
                'retry_count' => 10,
                'created_at' => '2025-07-23 10:38:41',
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'report_type' => 'TYPE_C',
                'report_config' => json_encode(['value' => 'Sample report_config 2']),
                'user_id' => 2,
                'status' => 'completed',
                'frequency' => 'weekly',
                'scheduled_at' => now(),
                'last_run_at' => '2025-07-23 11:00:00',
                'next_run_at' => '2025-07-25 11:00:00',
                'error_message' => 'Sample error_message 2',
                'output_path' => 'Sample output_path 2',
                'email_recipients' => 'sample2@scheduled_reports.com',
                'email_sent' => true,
                'retry_count' => 20,
                'created_at' => '2025-07-23 11:38:41',
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('scheduled_reports')->insert($row);
    }
}
}