<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditlogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('audit_logs')->truncate();

        // Insert sample data (table was empty)
        $data = [
            [
                'id' => 1,
                'user_id' => 1,
                'action' => 'Sample action 1',
                'details' => json_encode([]),
                'created_at' => '2025-07-23 10:38:32',
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'action' => 'Sample action 2',
                'details' => json_encode([]),
                'created_at' => '2025-07-23 11:38:32',
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('audit_logs')->insert($row);
    }
}
}