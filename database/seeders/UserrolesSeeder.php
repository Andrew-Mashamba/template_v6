<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserrolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('user_roles')->truncate();

        // Insert existing data
        $data = [
            [
                'id' => 1,
                'user_id' => 1,
                'role_id' => 1,
                'created_at' => '2025-07-17 16:25:47',
                'updated_at' => '2025-07-17 16:25:47',
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'role_id' => 1,
                'created_at' => '2025-07-17 16:25:47',
                'updated_at' => '2025-07-17 16:25:47',
            ],
            [
                'id' => 3,
                'user_id' => 1,
                'role_id' => 1,
                'created_at' => '2025-07-17 16:25:47',
                'updated_at' => '2025-07-17 16:25:47',
            ],
        ];

        foreach ($data as $row) {
            DB::table('user_roles')->insert($row);
    }
}
}