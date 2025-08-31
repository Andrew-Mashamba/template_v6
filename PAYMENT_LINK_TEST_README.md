# Payment Link Generation Tests

This document explains how to test the payment link generation functionality in the `app/Http/Livewire/Clients/Clients.php` file.

## Overview

The payment link generation functionality is implemented in the `save()` method of the Clients component. It generates payment links for new member registrations using the `PaymentLinkService`.

## Test Files Created

### 1. Feature Test: `tests/Feature/Livewire/PaymentLinkGenerationTest.php`

This is a comprehensive PHPUnit test that covers:

- **Successful payment link generation** - Tests the happy path with valid data
- **Error handling and fallback** - Tests when the payment service fails
- **Empty payment items handling** - Tests graceful handling of no payment items
- **Payment data structure validation** - Validates the structure of payment data
- **Logging verification** - Ensures proper logging of events
- **Partial payment modes** - Tests different payment modes (full vs partial)

### 2. Test Runner Script: `test-payment-link-generation.php`

A standalone script that can be run directly to test the payment link functionality:

```bash
php test-payment-link-generation.php
```

## How to Run the Tests

### Option 1: Run PHPUnit Tests

```bash
# Run all payment link tests
php artisan test tests/Feature/Livewire/PaymentLinkGenerationTest.php

# Run specific test method
php artisan test --filter it_generates_payment_link_successfully_for_new_member

# Run with verbose output
php artisan test tests/Feature/Livewire/PaymentLinkGenerationTest.php -v
```

### Option 2: Run Standalone Test Script

```bash
php test-payment-link-generation.php
```

### Option 3: Run with Coverage

```bash
# Run with coverage report
php artisan test tests/Feature/Livewire/PaymentLinkGenerationTest.php --coverage

# Run with HTML coverage report
php artisan test tests/Feature/Livewire/PaymentLinkGenerationTest.php --coverage-html coverage/
```

## Test Coverage

The tests cover the following scenarios:

### 1. Successful Payment Link Generation
- ✅ Creates payment data with correct structure
- ✅ Calls PaymentLinkService with proper parameters
- ✅ Stores payment link in database
- ✅ Handles multiple payment items
- ✅ Validates response structure

### 2. Error Handling
- ✅ Falls back to legacy URL when payment service fails
- ✅ Logs errors appropriately
- ✅ Continues member registration process
- ✅ Handles exceptions gracefully

### 3. Data Validation
- ✅ Validates payment data structure
- ✅ Ensures all required fields are present
- ✅ Validates payment items structure
- ✅ Checks customer information accuracy

### 4. Database Integration
- ✅ Verifies bills table structure
- ✅ Tests payment link storage
- ✅ Validates database constraints
- ✅ Checks required columns exist

### 5. Configuration Testing
- ✅ Tests environment variables
- ✅ Validates institution configuration
- ✅ Checks service dependencies

## Key Test Assertions

### Payment Data Structure
```php
$paymentData = [
    'description' => 'SACCOS Member Registration - ' . $account_name,
    'target' => 'individual',
    'customer_reference' => $this->client_number,
    'customer_name' => $account_name,
    'customer_phone' => $this->phone_number,
    'customer_email' => $this->email,
    'expires_at' => now()->addDays(7)->toIso8601String(),
    'items' => $items
];
```

### Database Assertions
```php
$this->assertDatabaseHas('bills', [
    'client_number' => 1001,
    'payment_link' => $expectedPaymentUrl,
    'payment_link_id' => $expectedLinkId,
    'payment_link_generated_at' => now()->toDateString()
]);
```

### Service Mocking
```php
$this->mockPaymentService->shouldReceive('generateUniversalPaymentLink')
    ->once()
    ->andReturn([
        'data' => [
            'payment_url' => $expectedPaymentUrl,
            'link_id' => $expectedLinkId,
            'total_amount' => $expectedTotalAmount
        ]
    ]);
```

## Prerequisites

Before running the tests, ensure:

1. **Database Setup**: Tests use `RefreshDatabase` trait, so they will create and destroy test databases
2. **Environment Variables**: Set up your `.env.testing` file with required variables
3. **Dependencies**: All required services should be available
4. **Mocking**: Tests use Mockery for service mocking

## Environment Variables Required

```env
PAYMENT_LINK=https://payment.example.com
DB_CONNECTION=testing
```

## Troubleshooting

### Common Issues

1. **Database Connection**: Ensure your testing database is configured
2. **Service Dependencies**: Make sure all required services are available
3. **Mocking Issues**: Check that Mockery is properly configured
4. **Environment Variables**: Verify all required env vars are set

### Debug Mode

To run tests in debug mode:

```bash
# Enable debug output
php artisan test tests/Feature/Livewire/PaymentLinkGenerationTest.php --verbose

# Run with detailed error reporting
php artisan test tests/Feature/Livewire/PaymentLinkGenerationTest.php --stop-on-failure
```

## Integration with CI/CD

Add to your CI/CD pipeline:

```yaml
# Example GitHub Actions
- name: Run Payment Link Tests
  run: |
    php artisan test tests/Feature/Livewire/PaymentLinkGenerationTest.php
    php test-payment-link-generation.php
```

## Performance Considerations

- Tests use database transactions for isolation
- Mocking reduces external service dependencies
- Tests are designed to run quickly (< 30 seconds total)
- Database cleanup is automatic

## Contributing

When adding new payment link features:

1. Add corresponding test cases
2. Update this README
3. Ensure all tests pass
4. Add integration tests for new scenarios

## Support

For issues with the tests:

1. Check the test output for specific error messages
2. Verify database configuration
3. Ensure all dependencies are installed
4. Check environment variable configuration
