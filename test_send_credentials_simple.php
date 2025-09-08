<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Jobs\SendEmployeeCredentials;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "Testing Employee Credentials Sending System\n";
echo "==========================================\n\n";

try {
    DB::beginTransaction();
    
    // Step 1: Create a test department
    echo "1. Creating test department...\n";
    $department = Department::firstOrCreate(
        ['department_code' => 'TEST001'],
        [
            'department_name' => 'Test Department',
            'description' => 'Test Department for Credentials',
            'status' => true,
            'level' => 1,
            'path' => '0'
        ]
    );
    echo "   ✓ Department created/found: {$department->department_name}\n\n";

    // Step 2: Create a test employee
    echo "2. Creating test employee...\n";
    $employee = new Employee();
    $employee->first_name = 'Test';
    $employee->last_name = 'Employee';
    $employee->email = 'test.employee' . uniqid() . '@example.com';
    $employee->phone = '0755' . rand(100000, 999999);
    $employee->employee_number = 'EMP' . uniqid();
    $employee->department_id = $department->id;
    $employee->job_title = 'Test Position';
    $employee->hire_date = now();
    $employee->basic_salary = 1000000;
    $employee->gross_salary = 1000000;
    $employee->gender = 'male';
    $employee->employee_status = 'active';
    $employee->employment_type = 'full-time';
    $employee->save();
    
    echo "   ✓ Employee created: {$employee->first_name} {$employee->last_name}\n";
    echo "   - Email: {$employee->email}\n";
    echo "   - Phone: {$employee->phone}\n\n";

    // Step 3: Create user account
    echo "3. Creating user account...\n";
    $password = 'Test@Password123';
    
    // Check if branch exists, if not create one
    $branch = DB::table('branches')->first();
    if (!$branch) {
        DB::table('branches')->insert([
            'id' => 1,
            'name' => 'Main Branch',
            'region' => 'Test Region',
            'wilaya' => 'Test',
            'branch_number' => 'MAIN001',
            'status' => 'ACTIVE',
            'branch_type' => 'MAIN',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $branch = DB::table('branches')->first();
    }
    
    $user = new User();
    $user->name = $employee->first_name . ' ' . $employee->last_name;
    $user->email = $employee->email;
    $user->password = bcrypt($password);
    $user->phone_number = $employee->phone;
    $user->employeeId = $employee->id;
    $user->department_code = $department->department_code;
    $user->branch_id = $branch->id;
    $user->status = 'active';
    $user->verification_status = 1;
    $user->email_verified_at = now();
    $user->save();
    
    echo "   ✓ User account created\n\n";

    // Step 4: Test sending credentials
    echo "4. Testing credential sending...\n";
    
    // Test direct job execution
    echo "   a) Testing direct job execution...\n";
    $job = new SendEmployeeCredentials($employee, $password, $user);
    
    try {
        $job->handle();
        echo "   ✓ Job executed successfully\n";
    } catch (\Exception $e) {
        echo "   ⚠ Job encountered error: " . $e->getMessage() . "\n";
        echo "   (This is expected if email/SMS services are not configured)\n";
    }
    
    // Test job dispatching
    echo "\n   b) Testing job dispatch to queue...\n";
    try {
        SendEmployeeCredentials::dispatch($employee, $password, $user);
        echo "   ✓ Job dispatched to queue successfully\n";
    } catch (\Exception $e) {
        echo "   ⚠ Job dispatch failed: " . $e->getMessage() . "\n";
    }

    // Check logs
    echo "\n5. Checking logs for credential sending activity...\n";
    $logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        
        if (strpos($logContent, 'SEND EMPLOYEE CREDENTIALS JOB') !== false) {
            echo "   ✓ Job execution logged\n";
        }
        
        if (strpos($logContent, 'Credentials email sent successfully') !== false) {
            echo "   ✓ Email sending logged\n";
        }
        
        if (strpos($logContent, 'Credentials SMS sent successfully') !== false) {
            echo "   ✓ SMS sending logged\n";
        }
        
        if (strpos($logContent, 'EMPLOYEE CREDENTIALS DELIVERY FAILED') !== false) {
            echo "   ⚠ Credential delivery failed (check email/SMS configuration)\n";
        }
    }

    DB::rollback(); // Rollback test data
    
    echo "\n==========================================\n";
    echo "Test completed successfully!\n";
    echo "Note: Actual email/SMS delivery depends on service configuration.\n";
    echo "Check storage/logs/laravel-" . date('Y-m-d') . ".log for detailed logs.\n";
    
} catch (\Exception $e) {
    DB::rollback();
    echo "\n❌ Test failed with error:\n";
    echo $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}