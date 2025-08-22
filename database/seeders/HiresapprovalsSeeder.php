<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HiresapprovalsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('hires_approvals')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'user_id' => 1,
                'user_name' => 'Sample user_name',
                'status' => 'pending',
                'employee_id' => 1,
                'created_at' => '2025-07-23 10:38:36',
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'user_name' => 'Sample user_name',
                'status' => 'inactive',
                'employee_id' => 2,
                'created_at' => '2025-07-23 11:38:36',
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('hires_approvals')->insert($row);
    }
}
}