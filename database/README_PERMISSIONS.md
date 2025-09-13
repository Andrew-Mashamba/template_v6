# SACCOS System Permissions Setup

## Overview
This document describes the permissions system and how to ensure super admin access is maintained after migrations.

## Permissions System Structure

### Total Permissions: 301
Permissions are organized across 33 modules covering all system functionality:
- Dashboard, Branches, Clients/Members
- Shares, Savings, Deposits, Loans
- Accounting, HR, Approvals
- Reports, User Management
- And many more...

## Super Admin Setup

### Default Super Admin
- **User ID**: 1
- **Name**: Andrew S. Mashamba
- **Email**: andrew.s.mashamba@gmail.com
- **Role**: IT Manager (Information Systems Department)
- **Permissions**: All 301 system permissions

## Running Permissions After Migration

### Method 1: Run Complete Seeders (Recommended)
```bash
# This will run all seeders including permissions assignment
php artisan migrate:fresh --seed
```

### Method 2: Run Permission Seeders Only
```bash
# Step 1: Create/update all system permissions
php artisan db:seed --class=SystemPermissionsSeeder

# Step 2: Assign all permissions to super admin
php artisan db:seed --class=AssignSuperAdminPermissionsSeeder
```

### Method 3: Use Artisan Command
```bash
# Quick command to assign super admin permissions
php artisan permissions:super-admin

# Force reassignment even if permissions exist
php artisan permissions:super-admin --force
```

### Method 4: Use Shell Script
```bash
# Run the automated script
./database/scripts/post-migration-permissions.sh
```

## Seeders Included

1. **SystemPermissionsSeeder.php**
   - Creates all 301 system permissions
   - Organized by module
   - Each permission follows format: `module.action`

2. **AssignSuperAdminPermissionsSeeder.php**
   - Assigns all permissions to User ID 1
   - Creates IT Manager role if doesn't exist
   - Runs automatically at end of DatabaseSeeder

3. **SACCOSRolesSeeder.php**
   - Creates 85 roles across 16 departments
   - Includes hierarchical role structure

4. **SACCOSSubRolesSeeder.php**
   - Creates 72 sub-roles
   - Includes deputy, assistant, and acting roles

## Verification

### Check User Permissions
```bash
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $role = $user->roles()->first();
>>> echo $role->permissions()->count();
# Should output: 301
```

### Check Specific Permissions
```bash
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $role = $user->roles()->first();
>>> $role->permissions()->where('module', 'System')->pluck('name');
# Should show all system permissions
```

## Troubleshooting

### If User ID 1 Has No Permissions
1. Run: `php artisan permissions:super-admin --force`
2. Or run: `./database/scripts/post-migration-permissions.sh`

### If Permissions Table is Empty
1. Run: `php artisan db:seed --class=SystemPermissionsSeeder`
2. Then: `php artisan permissions:super-admin`

### If User ID 1 Doesn't Exist
1. Create the user first
2. Then assign permissions using the command

## Important Notes

- The AssignSuperAdminPermissionsSeeder is included in DatabaseSeeder and runs automatically
- It's safe to run multiple times - it will sync permissions
- The IT Manager role is created automatically if it doesn't exist
- All 301 permissions give complete system access

## Module Permission Counts

| Module | Permission Count |
|--------|-----------------|
| Loans | 14 |
| Human Resources | 14 |
| Accounting | 14 |
| User Management | 11 |
| Cash Management | 10 |
| Billing | 10 |
| Teller Management | 10 |
| Savings | 10 |
| Clients/Members | 10 |
| Reports | 10 |
| System | 10 |
| Other modules | 7-9 each |
| **Total** | **301** |

---
Last Updated: 2025-09-13