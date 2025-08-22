<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core system seeders
            InstitutionsSeeder::class,
            BranchesSeeder::class,
            DepartmentsSeeder::class,
            
            // User and role management
            RolesSeeder::class,
            PermissionsSeeder::class,
            UsersSeeder::class,
            
            // Menu system
            ConsolidatedMenuSeeder::class,
            RoleMenuActionsSeeder::class,
            
            // Member management
            ClientsSeeder::class,
            
            // Financial products
            ServicesSeeder::class,
            SubProductsSeeder::class,
            LoanSubProductsSeeder::class,
            
            // Accounting
            AccountsSeeder::class,
            // GLaccountsSeeder::class, // Table doesn't exist in migrations
            
            // Other seeders (in alphabetical order)
            BanksSeeder::class,
            ChargesSeeder::class,
            CollateralTypesSeeder::class,
            ComplaintCategoriesSeeder::class,
            ComplaintStatusesSeeder::class,
            CurrenciesSeeder::class,
            DistrictsSeeder::class,
            DocumenttypesSeeder::class,
            MobileNetworksSeeder::class,
            PaymentmethodsSeeder::class,
            ProcessCodeConfigsSeeder::class,
            // RegionsSeeder::class, // Table 'regions' doesn't exist in migrations
            // WardsSeeder::class, // Table 'wards' doesn't exist in migrations
        ]);
    }
}
