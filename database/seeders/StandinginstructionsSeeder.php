<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StandinginstructionsSeeder extends Seeder
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
        DB::table('standing_instructions')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'member_id' => 1,
                'source_account_number' => 10,
                'source_bank_id' => 1,
                'destination_account_name' => 'Sample destination_account_name',
                'bank' => 'Sample bank 1',
                'destination_account_id' => 1,
                'saccos_branch_id' => 1,
                'amount' => 1000,
                'frequency' => 'Sample frequency 1',
                'start_date' => '2025-07-24',
                'end_date' => '2025-07-24',
                'reference_number' => '000001',
                'service' => 'Sample service 1',
                'status' => 'pending',
                'created_at' => '2025-07-23 10:38:42',
                'updated_at' => now(),
                'description' => 'This is a sample description for standing_instructions record 1',
            ],
            [
                'id' => 2,
                'member_id' => 2,
                'source_account_number' => 20,
                'source_bank_id' => 2,
                'destination_account_name' => 'Sample destination_account_name',
                'bank' => 'Sample bank 2',
                'destination_account_id' => 2,
                'saccos_branch_id' => 2,
                'amount' => 2000,
                'frequency' => 'Sample frequency 2',
                'start_date' => '2025-07-25',
                'end_date' => '2025-07-25',
                'reference_number' => '000002',
                'service' => 'Sample service 2',
                'status' => 'inactive',
                'created_at' => '2025-07-23 11:38:42',
                'updated_at' => now(),
                'description' => 'This is a sample description for standing_instructions record 2',
            ],
        ];

        foreach ($data as $row) {
            DB::table('standing_instructions')->updateOrInsert(
                ['id' => $row['id']],
                $row
            );
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