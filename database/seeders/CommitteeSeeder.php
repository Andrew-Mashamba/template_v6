<?php

namespace Database\Seeders;

use App\Models\Committee;
use App\Models\Department;
use App\Models\Institution;
use Illuminate\Database\Seeder;

class CommitteeSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::where('code', 'NBC001')->first();
        $financeDept = Department::where('department_code', 'FIN')->first();
        $operationsDept = Department::where('department_code', 'OPS')->first();

        // Create Loan Committees
        Committee::create([
            'name' => 'Loan Approval Committee',
            'description' => 'Primary loan approval committee',
            'status' => true,
            'department_id' => $operationsDept->id,
            'loan_category' => 'GENERAL',
            'min_approvals_required' => 2,
            'approval_order' => 1,
            'type' => 'LOAN',
            'level' => 1
        ]);

        Committee::create([
            'name' => 'Loan Review Committee',
            'description' => 'Secondary loan review committee',
            'status' => true,
            'department_id' => $financeDept->id,
            'loan_category' => 'GENERAL',
            'min_approvals_required' => 1,
            'approval_order' => 2,
            'type' => 'LOAN',
            'level' => 2
        ]);

        // Create Audit Committee
        Committee::create([
            'name' => 'Internal Audit Committee',
            'description' => 'Internal audit and compliance committee',
            'status' => true,
            'department_id' => $financeDept->id,
            'min_approvals_required' => 1,
            'approval_order' => 1,
            'type' => 'AUDIT',
            'level' => 1
        ]);

        // Create Compliance Committee
        Committee::create([
            'name' => 'Compliance Committee',
            'description' => 'Regulatory compliance committee',
            'status' => true,
            'department_id' => $financeDept->id,
            'min_approvals_required' => 1,
            'approval_order' => 1,
            'type' => 'COMPLIANCE',
            'level' => 1
        ]);
    }
}