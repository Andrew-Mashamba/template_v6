<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationlogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement('SET session_replication_role = replica;');
        }
        
        try {
        // Clear existing data
        DB::table('notification_logs')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'process_id' => Str::uuid(),
                'recipient_type' => 'TYPE_B',
                'recipient_id' => 1,
                'recipient_email' => 'sample1@notification_logs.com',
                'recipient_phone' => '+255700000001',
                'notification_type' => 'TYPE_B',
                'channel' => 'Sample channel 1',
                'status' => 'pending',
                'error_message' => 'Sample error_message 1',
                'error_details' => json_encode(['value' => 'Sample error_details 1']),
                'sent_at' => null,
                'delivered_at' => null,
                'failed_at' => null,
                'response_data' => json_encode(['value' => 'Sample response_data 1']),
                'control_numbers' => 000001,
                'payment_link' => 'Sample payment_link 1',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-07-23 10:38:39',
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'process_id' => Str::uuid(),
                'recipient_type' => 'TYPE_C',
                'recipient_id' => 2,
                'recipient_email' => 'sample2@notification_logs.com',
                'recipient_phone' => '+255700000002',
                'notification_type' => 'TYPE_C',
                'channel' => 'Sample channel 2',
                'status' => 'sent',
                'error_message' => 'Sample error_message 2',
                'error_details' => json_encode(['value' => 'Sample error_details 2']),
                'sent_at' => null,
                'delivered_at' => now(),
                'failed_at' => null,
                'response_data' => json_encode(['value' => 'Sample response_data 2']),
                'control_numbers' => 000002,
                'payment_link' => 'Sample payment_link 2',
                'created_by' => 1,
                'updated_by' => 2,
                'created_at' => '2025-07-23 11:38:39',
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('notification_logs')->insert($row);
        }
    
        
        
        } finally {
            // Re-enable foreign key checks
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif (DB::getDriverName() === 'pgsql') {
                DB::statement('SET session_replication_role = DEFAULT;');
                }
    }
}
}