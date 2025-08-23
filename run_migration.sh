#!/bin/bash

echo "🚀 Starting fresh migration and seeding process..."
echo ""

# Step 1: Run fresh migration
echo "Step 1: Running fresh migration..."
php artisan migrate:fresh

# Check if migration succeeded
if [ $? -ne 0 ]; then
    echo "❌ Migration failed!"
    exit 1
fi

echo "✅ Migration completed successfully"
echo ""

# Step 2: Run database seeders
echo "Step 2: Running database seeders..."
echo "Using direct PHP call to avoid transaction rollback issues..."
php -r "
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    (new Database\Seeders\DatabaseSeeder())->run();
"

# Check if seeding succeeded
if [ $? -ne 0 ]; then
    echo "❌ Seeding failed!"
    exit 1
fi

echo "✅ Seeding completed successfully"
echo ""

# Step 3: Verify data
echo "Step 3: Verifying data..."
php artisan tinker --execute="
    echo 'Database Summary:';
    echo '================';
    echo 'Users: ' . \App\Models\User::count();
    echo 'Branches: ' . \DB::table('branches')->count();
    echo 'Institutions: ' . \DB::table('institutions')->count();
    echo 'Clients: ' . \DB::table('clients')->count();
    echo 'Accounts: ' . \DB::table('accounts')->count();
    echo 'Loans: ' . \DB::table('loans')->count();
"

echo ""
echo "🎉 Migration and seeding process completed!"
