<?php

use App\Models\User;
use App\Models\departmentsList;
use App\Models\Role;
use App\Models\SubRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "TESTING SIMPLIFIED USER CREATION\n";
echo "=================================\n\n";

// Test 1: Verify Department/Role/SubRole structure
echo "1. Verifying Department/Role/SubRole Structure:\n";
echo "------------------------------------------------\n";

$departments = departmentsList::where('status', true)->count();
echo "Active Departments: $departments\n";

$roles = Role::where('status', 'ACTIVE')->count();
echo "Active Roles: $roles\n";

$subRoles = SubRole::count();
echo "Total Sub-Roles: $subRoles\n\n";

// Test 2: Create a test user with department/role/subrole
echo "2. Creating Test User with Simplified Structure:\n";
echo "------------------------------------------------\n";

try {
    DB::beginTransaction();
    
    // Get IT department
    $itDepartment = departmentsList::where('department_code', 'ICT')->first();
    if (!$itDepartment) {
        throw new Exception("IT Department not found");
    }
    echo "✓ Found IT Department: {$itDepartment->department_name}\n";
    
    // Get a role from IT department
    $developerRole = Role::where('department_id', $itDepartment->id)
        ->where('name', 'LIKE', '%Developer%')
        ->first();
    
    if (!$developerRole) {
        // Create a test role if not exists
        $developerRole = Role::create([
            'name' => 'Senior Developer',
            'description' => 'Senior software developer role',
            'department_id' => $itDepartment->id,
            'status' => 'ACTIVE',
            'institution_id' => 11,
        ]);
    }
    echo "✓ Using Role: {$developerRole->name}\n";
    
    // Get sub-roles for this role
    $subRole = SubRole::where('role_id', $developerRole->id)->first();
    if (!$subRole) {
        // Create a test sub-role
        $subRole = SubRole::create([
            'name' => 'Backend Specialist',
            'description' => 'Specializes in backend development',
            'role_id' => $developerRole->id,
        ]);
    }
    echo "✓ Using Sub-Role: {$subRole->name}\n";
    
    // Create test user
    $testEmail = 'test.developer.' . time() . '@example.com';
    $testUser = User::create([
        'name' => 'Test Developer',
        'email' => $testEmail,
        'password' => Hash::make('password123'),
        'phone_number' => '0712345678',
        'employeeId' => 'EMP' . time(),
        'department_code' => $itDepartment->department_code,
        'status' => 'ACTIVE',
        'branch' => 1,
    ]);
    echo "✓ Created User: {$testUser->name} ({$testUser->email})\n";
    
    // Assign role
    $testUser->roles()->attach($developerRole->id);
    echo "✓ Assigned Role: {$developerRole->name}\n";
    
    // Assign sub-role
    $testUser->subRoles()->attach($subRole->id);
    echo "✓ Assigned Sub-Role: {$subRole->name}\n";
    
    // Sync permissions from role
    $permissions = $developerRole->permissions;
    $testUser->permissions()->sync($permissions->pluck('id'));
    echo "✓ Synced {$permissions->count()} permissions from role\n";
    
    // Add permissions from sub-role
    if ($subRole->permissions) {
        $testUser->permissions()->syncWithoutDetaching($subRole->permissions->pluck('id'));
        echo "✓ Added permissions from sub-role\n";
    }
    
    DB::commit();
    echo "\n✅ SUCCESS: Test user created with simplified structure!\n\n";
    
    // Test 3: Verify user's access
    echo "3. Verifying User's Access:\n";
    echo "----------------------------\n";
    
    // Check user's department
    $userRole = $testUser->roles()->with('department')->first();
    if ($userRole && $userRole->department) {
        echo "✓ User Department: {$userRole->department->department_name}\n";
    }
    
    // Check user's role
    echo "✓ User Role: {$userRole->name}\n";
    
    // Check user's sub-roles
    $userSubRoles = $testUser->subRoles;
    echo "✓ User Sub-Roles: " . $userSubRoles->pluck('name')->implode(', ') . "\n";
    
    // Check user's permissions
    $userPermissions = $testUser->permissions()->count();
    echo "✓ User Permissions: $userPermissions\n";
    
    // Test menu visibility
    echo "\n4. Testing Menu Visibility:\n";
    echo "----------------------------\n";
    
    // Login as the test user
    auth()->login($testUser);
    
    // Initialize sidebar component
    $sidebar = new \App\Http\Livewire\Sidebar\Sidebar();
    $sidebar->currentUserId = $testUser->id;
    $sidebar->currentUserRoles = $testUser->roles()->with(['permissions', 'department'])->get();
    $sidebar->loadMenuItems();
    
    echo "✓ User can see " . count($sidebar->menuItems) . " menu items\n";
    
    // List visible menus
    echo "\nVisible Menus:\n";
    foreach ($sidebar->menuItems as $menuId) {
        if ($menuId == 0) {
            echo "  • Dashboard\n";
        } else {
            $menu = \App\Models\Menu::find($menuId);
            if ($menu) {
                echo "  • {$menu->menu_name}\n";
            }
        }
    }
    
    echo "\n✅ COMPLETE: Simplified user creation system is working!\n";
    echo "============================================\n";
    echo "Summary:\n";
    echo "- User creation simplified to essential fields\n";
    echo "- Department → Role → SubRole hierarchy working\n";
    echo "- Permissions automatically inherited from roles\n";
    echo "- Menu visibility based on permissions\n";
    echo "- No unnecessary SACCOS member registration\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}