# AI Assistant Guide: Implementing Permissions in SACCOS Modules

## Purpose
This document provides instructions for AI assistants to implement the SACCOS permission system in any module. Follow these steps exactly when a user asks you to add permissions to a module.

## Pre-Implementation Checklist

Before starting, verify:
1. ✅ The module name exists in the permissions table (check with: `App\Models\Permission::where('name', 'like', 'MODULE_NAME.%')->count()`)
2. ✅ The Livewire component exists in `app/Http/Livewire/MODULE_NAME/`
3. ✅ The blade views exist in `resources/views/livewire/MODULE_NAME/`

## Step 1: Analyze Current Implementation

First, read the target component file to understand:
- What permission checking method is currently used
- What properties exist for permissions
- What methods need permission checks

**Command to analyze:**
```bash
# Read the main component
Read: app/Http/Livewire/MODULE_NAME/MODULE_COMPONENT.php

# Check for existing permission properties
Grep: "canView|canEdit|canDelete|canCreate|hasPermission|userPermissions" in above file

# Check current trait usage
Grep: "use.*HasRoles|use.*WithModulePermissions" in above file
```

## Step 2: Implement Permission System

### 2.1: Update the Component Class

**Use MultiEdit to make these changes:**

```php
# Change 1: Update the use statements
OLD: use App\Traits\HasRoles;
NEW: use App\Traits\Livewire\WithModulePermissions;

# Change 2: Update trait usage
OLD: use HasRoles;
NEW: use WithModulePermissions;

# Change 3: Remove old permission properties (if they exist)
REMOVE these lines:
- public $canView = false;
- public $canEdit = false;
- public $canDelete = false;
- public $canCreate = false;
- public $canApprove = false;
- public $userPermissions = [];
- private $actionPermissions = [...]

# Change 4: Update mount() method
OLD: public function mount()
{
    $this->loadUserPermissions();
    // other code
}

NEW: public function mount()
{
    // Initialize the permission system for this module
    $this->initializeWithModulePermissions();
    // other code
}

# Change 5: Add getModuleName() method after mount()
ADD after mount() method:
/**
 * Override to specify the module name for permissions
 * 
 * @return string
 */
protected function getModuleName(): string
{
    return 'MODULE_NAME'; // Replace MODULE_NAME with actual module
}

# Change 6: Remove old permission methods
REMOVE these entire methods if they exist:
- private function loadUserPermissions() { ... }
- private function hasPermission($permission) { ... }
- private function setPermissions() { ... }
```

### 2.2: Update Permission Checks in Methods

**For each action method, update permission checking:**

```php
# Pattern for CREATE actions
OLD: if (!$this->hasPermission('create')) {
    session()->flash('message', 'You do not have permission to create');
    return;
}

NEW: if (!$this->authorize('create', 'You do not have permission to create MODULE_NAME')) {
    return;
}

# Pattern for EDIT actions
OLD: if (!$this->canEdit) { ... }

NEW: if (!$this->authorize('edit', 'You do not have permission to edit MODULE_NAME')) {
    return;
}

# Pattern for DELETE actions
OLD: if (!in_array('delete', $this->userPermissions)) { ... }

NEW: if (!$this->authorize('delete', 'You do not have permission to delete MODULE_NAME')) {
    return;
}

# Pattern for VIEW actions (usually silent)
OLD: if ($this->hasPermission('view'))

NEW: if ($this->can('view'))
```

### 2.3: Update render() Method

**Update the render method to pass permissions:**

```php
OLD: return view('livewire.MODULE_NAME.VIEW_NAME', [
    'canCreate' => $this->canCreate,
    'canEdit' => $this->canEdit,
    'canDelete' => $this->canDelete,
    'data' => $data
]);

NEW: return view('livewire.MODULE_NAME.VIEW_NAME', array_merge(
    $this->permissions,
    [
        'data' => $data,
        'permissions' => $this->permissions
    ]
));
```

## Step 3: Update Blade Views

### 3.1: Find All Permission Checks in Views

```bash
# Search for permission usage in views
Grep: "@if.*can|@can|canView|canEdit|canDelete|canCreate" 
Path: resources/views/livewire/MODULE_NAME/
```

### 3.2: Update Permission Checks

**Use Edit or MultiEdit for these patterns:**

```blade
# Pattern 1: Simple permission checks
OLD: @if($canCreate)
NEW: @if($permissions['canCreate'] ?? false)

# Pattern 2: Multiple permission checks
OLD: @if($canEdit || $canDelete)
NEW: @if(($permissions['canEdit'] ?? false) || ($permissions['canDelete'] ?? false))

# Pattern 3: Component property checks
OLD: @if($this->canView)
NEW: @if($permissions['canView'] ?? false)

# Pattern 4: Direct permission checks
OLD: @can('MODULE_NAME.create')
NEW: @if($permissions['canCreate'] ?? false)
```

## Step 4: Handle Sub-Components

If the module has multiple components (e.g., ModuleTable, ModuleForm), repeat Steps 2-3 for each:

```bash
# Find all components in the module
Glob: "*.php" 
Path: app/Http/Livewire/MODULE_NAME/

# For each component found, apply the same changes
```

## Step 5: Verify Implementation

### 5.1: Check for Errors

```bash
# Clear cache
Bash: php artisan cache:clear

# Check for syntax errors
Bash: php -l app/Http/Livewire/MODULE_NAME/COMPONENT.php
```

### 5.2: Test Permission Functionality

Create a test script:

```php
Write: test_MODULE_permissions.php
Content:
<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PermissionService;

$user = App\Models\User::find(10); // Use test user ID
$service = new App\Services\PermissionService($user);

echo "Testing MODULE_NAME permissions for user: {$user->name}\n";
$actions = ['view', 'create', 'edit', 'delete'];

foreach($actions as $action) {
    $has = $service->can('MODULE_NAME', $action);
    echo "MODULE_NAME.{$action}: " . ($has ? 'YES' : 'NO') . "\n";
}

# Run test
Bash: php test_MODULE_permissions.php

# Clean up
Bash: rm test_MODULE_permissions.php
```

## Common Module Patterns

### Pattern A: Simple CRUD Module
Modules like: branches, clients, products
```
Required changes:
- Main component class
- Table/list view
- Create/edit modals
- Delete confirmations
```

### Pattern B: Complex Workflow Module
Modules like: loans, approvals, procurement
```
Required changes:
- Main component class
- Multiple sub-components
- Approval workflows
- Status-based permissions
```

### Pattern C: Reporting Module
Modules like: reports, reconciliation
```
Required changes:
- Main component class
- Export functionality
- View restrictions
- Filter permissions
```

## Module-Specific Permissions

When implementing, check for these module-specific permissions:

### Financial Modules (loans, savings, deposits)
- `view` - View records
- `create` - Create new records
- `edit` - Edit existing records
- `delete` - Delete records
- `approve` - Approve transactions
- `disburse` - Disburse funds
- `reverse` - Reverse transactions
- `export` - Export data

### Administrative Modules (users, branches, roles)
- `view` - View records
- `create` - Create new records
- `edit` - Edit records
- `delete` - Delete records
- `activate` - Activate/Deactivate
- `assign_users` - Assign users
- `manage_settings` - Manage settings
- `manage_permissions` - Manage permissions

### Reporting Modules (reports, reconciliation)
- `view` - View reports
- `generate` - Generate reports
- `export` - Export reports
- `schedule` - Schedule reports
- `customize` - Customize reports

## Error Handling

### Error 1: "Cannot redeclare property $module"
**Solution**: Don't add `protected $module = 'name';`
Instead, override the method:
```php
protected function getModuleName(): string
{
    return 'MODULE_NAME';
}
```

### Error 2: "Call to undefined method hasPermission()"
**Solution**: The old method is removed. Replace with:
- `$this->can('action')` for silent checks
- `$this->authorize('action')` for user-facing actions

### Error 3: "Undefined array key 'canView'"
**Solution**: In blade files, always use null coalescing:
```blade
@if($permissions['canView'] ?? false)
```

## Validation Steps

After implementation, verify:

1. ✅ Component loads without errors
2. ✅ mount() initializes permissions
3. ✅ getModuleName() returns correct module name
4. ✅ All action methods check permissions
5. ✅ render() passes permissions to view
6. ✅ Blade views use $permissions array correctly
7. ✅ No references to old permission methods remain
8. ✅ Cache cleared after changes

## Quick Implementation Script

For standard modules, use this sequence:

```bash
# 1. Read current implementation
Read: app/Http/Livewire/MODULE_NAME/MAIN_COMPONENT.php

# 2. Apply all changes at once using MultiEdit
MultiEdit: app/Http/Livewire/MODULE_NAME/MAIN_COMPONENT.php
- Update use statements
- Change trait
- Remove old properties
- Update mount()
- Add getModuleName()
- Update all permission checks
- Update render()

# 3. Update main blade view
MultiEdit: resources/views/livewire/MODULE_NAME/MAIN_VIEW.blade.php
- Update all @if($canXXX) to @if($permissions['canXxx'] ?? false)

# 4. Clear cache and test
Bash: php artisan cache:clear
```

## Module Priority List

When implementing across multiple modules, prioritize in this order:

1. **Critical Financial**: loans, savings, accounting
2. **User Management**: users, roles, branches
3. **Transaction Processing**: payments, transactions, teller
4. **Reporting**: reports, reconciliation
5. **Support Modules**: email, notifications, approvals
6. **Administrative**: hr, procurement, expenses

## Important Notes for AI

1. **Always use MultiEdit** when making multiple changes to reduce API calls
2. **Check file exists** before attempting to edit
3. **Clear cache** after implementation
4. **Test with user ID 10** (MASAKA MONA) as they have known permissions
5. **Don't create test files** unless specifically asked
6. **Use exact module names** from the permissions table
7. **Preserve existing business logic** - only update permission checking
8. **Keep error messages consistent** with module name

## Response Template

When implementing permissions, respond with:

```
I'll implement the permission system for the [MODULE] module.

[Step 1: Analyzing current implementation]
✓ Found component at: [path]
✓ Current permission method: [old method]
✓ Number of methods needing updates: [count]

[Step 2: Updating component]
✓ Updated trait to WithModulePermissions
✓ Added permission initialization in mount()
✓ Updated [X] method permission checks
✓ Updated render() to pass permissions

[Step 3: Updating views]
✓ Updated [X] permission checks in blade views

[Step 4: Testing]
✓ Cleared cache
✓ Verified no syntax errors

The [MODULE] module now uses the centralized permission system.
```

---

*Document Version: 1.0*
*For: AI Assistants implementing SACCOS permissions*
*Last Updated: September 2025*