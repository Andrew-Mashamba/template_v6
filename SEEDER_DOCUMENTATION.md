# Database Seeder Documentation

## Overview

This document describes the comprehensive database seeding system that has been generated for the NBC SACCOS application. The system includes both existing seeders and newly generated seeders that contain actual data from the database.

## Generated Seeders

### Newly Generated Seeders (from database data)

The following seeders were automatically generated from existing database data:

1. **GLaccountsSeeder.php** - General Ledger accounts (5 records)
2. **AccountsSeeder.php** - Chart of accounts (260 records)
3. **BillsSeeder.php** - Billing data (10 records)
4. **ComplaintsSeeder.php** - Customer complaints (8 records)
5. **LoansSeeder.php** - Loan records (2 records)

### Existing Seeders (updated)

The following existing seeders were updated with new data:

1. **CollateralTypesSeeder.php** - Collateral types (14 records)
2. **ComplaintCategoriesSeeder.php** - Complaint categories (8 records)
3. **ComplaintStatusesSeeder.php** - Complaint statuses (5 records)

## How to Use

### Option 1: Run All Seeders (Recommended)

```bash
php run_all_seeders.php
```

This script will:
- Run all seeders in the correct order
- Show progress for each seeder
- Provide a summary of successful and failed seeders
- Handle errors gracefully

### Option 2: Run Individual Seeders

```bash
php artisan db:seed --class=GLaccountsSeeder
php artisan db:seed --class=AccountsSeeder
php artisan db:seed --class=BillsSeeder
php artisan db:seed --class=ComplaintsSeeder
php artisan db:seed --class=LoansSeeder
```

### Option 3: Run via DatabaseSeeder

```bash
php artisan db:seed
```

This will run the DatabaseSeeder which includes all the new seeders.

## Seeder Generation Script

### Regenerate Seeders from Database

To regenerate seeders from current database data:

```bash
php generate_seeders.php
```

This script will:
- Scan all tables in the database
- Generate seeders for tables with data
- Skip system tables and temporary tables
- Update existing seeders with new data
- Create new seeders for tables without existing seeders

### Customization

You can modify the `generate_seeders.php` script to:
- Add/remove tables from the exclusion list
- Change the seeder naming convention
- Modify the data formatting
- Add custom logic for specific tables

## Seeder Structure

Each generated seeder follows this structure:

```php
<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TableNameSeeder extends Seeder
{
    public function run()
    {
        // Clear existing data
        DB::table('table_name')->truncate();

        // Insert data
        $data = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
                // ... all columns
            ],
            // ... all rows
        ];

        foreach ($data as $row) {
            DB::table('table_name')->insert($row);
        }
    }
}
```

## Data Types Handled

The seeder generator handles various data types:

- **Strings**: Properly escaped with single quotes
- **Numbers**: No quotes (integers, decimals)
- **Booleans**: `true` or `false`
- **Null values**: `null`
- **Dates/Timestamps**: Preserved as strings

## Excluded Tables

The following tables are excluded from seeder generation:

- System tables (`migrations`, `failed_jobs`, etc.)
- Audit/log tables
- Temporary tables
- Staging tables
- Tables with sensitive data
- Tables that should be populated by other processes

## Best Practices

1. **Backup First**: Always backup your database before running seeders
2. **Test Environment**: Test seeders in a development environment first
3. **Order Matters**: Some seeders depend on others, run them in the correct order
4. **Data Validation**: Verify the generated data is correct
5. **Customization**: Modify seeders as needed for your specific requirements

## Troubleshooting

### Common Issues

1. **Foreign Key Constraints**: Some seeders may fail due to missing foreign key data
2. **Large Data Sets**: Very large tables may cause memory issues
3. **Special Characters**: Some data may contain characters that need special handling

### Solutions

1. **Check Dependencies**: Ensure dependent tables are seeded first
2. **Increase Memory**: Increase PHP memory limit for large datasets
3. **Review Data**: Check the generated seeder files for any obvious issues

## Maintenance

### Updating Seeders

To update seeders with new data:

1. Run `php generate_seeders.php`
2. Review the changes
3. Test the updated seeders
4. Commit the changes

### Adding New Tables

To add new tables to the seeding process:

1. Add the table to the database
2. Run `php generate_seeders.php`
3. Add the new seeder to `DatabaseSeeder.php`
4. Test the new seeder

## Files Created/Modified

### New Files
- `generate_seeders.php` - Seeder generation script
- `run_all_seeders.php` - Comprehensive seeder runner
- `get_tables.php` - Database table listing utility
- `SEEDER_DOCUMENTATION.md` - This documentation

### Generated Seeders
- `database/seeders/GLaccountsSeeder.php`
- `database/seeders/AccountsSeeder.php`
- `database/seeders/BillsSeeder.php`
- `database/seeders/ComplaintsSeeder.php`
- `database/seeders/LoansSeeder.php`

### Updated Files
- `database/seeders/DatabaseSeeder.php` - Added new seeders
- `database/seeders/CollateralTypesSeeder.php` - Updated with new data
- `database/seeders/ComplaintCategoriesSeeder.php` - Updated with new data
- `database/seeders/ComplaintStatusesSeeder.php` - Updated with new data

## Support

For issues or questions about the seeding system:

1. Check the logs for error messages
2. Review the generated seeder files
3. Test individual seeders
4. Consult this documentation

---

**Last Updated**: July 22, 2025
**Generated By**: AI Assistant
**Version**: 1.0 