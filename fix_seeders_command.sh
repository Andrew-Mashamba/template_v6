#!/bin/bash

# List of seeders that use $this->command
SEEDERS=(
    "database/seeders/LoancollateralsSeeder.php"
    "database/seeders/OnboardingSeeder.php"
    "database/seeders/ApikeysSeeder.php"
    "database/seeders/AiinteractionsSeeder.php"
    "database/seeders/UseractionlogsSeeder.php"
    "database/seeders/AuditlogsSeeder.php"
    "database/seeders/ScheduledreportsSeeder.php"
    "database/seeders/HiresapprovalsSeeder.php"
    "database/seeders/BillsSeeder.php"
    "database/seeders/LockedamountsSeeder.php"
    "database/seeders/ExpensesSeeder.php"
    "database/seeders/SampleLoanCollateralSeeder.php"
    "database/seeders/VaultsSeeder.php"
)

for SEEDER in "${SEEDERS[@]}"; do
    echo "Fixing $SEEDER..."
    
    # Replace $this->command->info with conditional check
    sed -i '' 's/\$this->command->info(/if (\$this->command) \$this->command->info(/g' "$SEEDER"
    
    # Replace $this->command->warn with conditional check
    sed -i '' 's/\$this->command->warn(/if (\$this->command) \$this->command->warn(/g' "$SEEDER"
    
    # Replace $this->command->error with conditional check
    sed -i '' 's/\$this->command->error(/if (\$this->command) \$this->command->error(/g' "$SEEDER"
    
    # Fix any double conditionals that might have been created
    sed -i '' 's/if (\$this->command) if (\$this->command)/if (\$this->command)/g' "$SEEDER"
done

echo "All seeders fixed!"
