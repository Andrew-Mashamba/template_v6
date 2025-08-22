# Payment Link Implementation for Member Registration

## Overview
I've implemented payment link generation in the `save()` function of `app/Http/Livewire/Clients/Clients.php`. The implementation integrates with the PaymentLinkService to generate payment links for new member registrations.

## Implementation Details

### 1. **Service Integration**
- Added `use App\Services\PaymentLinkService;` import
- Creates an instance of PaymentLinkService when generating payment links

### 2. **Payment Items**
The implementation dynamically creates payment items by:
- Fetching all unpaid bills from the `bills` table for the client
- Joining with the `services` table to get service names
- Creating a payment item for each bill with:
  - Service reference: `{service_code}_{client_number}`
  - Service name from the services table
  - Bill amount from the bills table
  - Partial payment allowed based on the payment_mode field

### 3. **Payment Link Generation**
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

### 4. **Database Storage**
The generated payment link is stored in two places:

**Bills Table** (for each bill included in the payment):
- `payment_link` - The full payment URL
- `payment_link_id` - The unique link ID from the payment gateway
- `payment_link_generated_at` - Timestamp when the link was generated
- `payment_link_items` - JSON array of payment items returned by the gateway

**Clients Table** (for quick reference):
- `payment_link` - The full payment URL
- `payment_link_id` - The unique link ID from the payment gateway
- `payment_link_generated_at` - Timestamp when the link was generated

### 5. **Error Handling**
- Try-catch block handles any errors during payment link generation
- Falls back to legacy URL format if payment link generation fails
- All operations are logged for debugging

### 6. **Notification Integration**
The generated payment URL is passed to `ProcessMemberNotifications` job for sending to the member via email/SMS.

## Migrations Required
Run the following command to add payment link columns:
```bash
php artisan migrate
```

This will add columns to both tables:

**Bills Table:**
- `payment_link` (string, nullable)
- `payment_link_id` (string, nullable)
- `payment_link_generated_at` (timestamp, nullable)
- `payment_link_items` (json, nullable)

**Clients Table:**
- `payment_link` (string, nullable)
- `payment_link_id` (string, nullable)
- `payment_link_generated_at` (timestamp, nullable)

## Configuration
Ensure the following environment variables are set:
```env
PAYMENT_GATEWAY_BASE_URL=http://172.240.241.188
PAYMENT_GATEWAY_API_KEY=your_api_key
PAYMENT_GATEWAY_API_SECRET=your_api_secret
PAYMENT_LINK=your_fallback_payment_link_base_url
```

## Usage
The payment link is automatically generated when a new member is registered through the multi-step registration form. The link includes all mandatory fees and contributions specified during registration.

## Testing
To test the payment link generation:
1. Go to the Members/Clients page
2. Click "Add New Member"
3. Fill in all required information through the multi-step form
4. On submission, check the logs for payment link generation
5. Verify the payment link is stored in the database

## Logs
All payment link operations are logged:
- Successful generation: `[INFO] Payment link generated successfully`
- Failures: `[ERROR] Failed to generate payment link`
- Fallbacks: `[WARNING] Payment link generation did not return URL`