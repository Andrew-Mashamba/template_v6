#!/bin/bash

# Email System Setup Script
# This script sets up the complete email system for the SACCOS Laravel application

echo "=========================================="
echo "   SACCOS Email System Setup"
echo "=========================================="
echo ""

# Check if running from project root
if [ ! -f "artisan" ]; then
    echo "Error: This script must be run from the Laravel project root directory."
    exit 1
fi

# Function to check command success
check_status() {
    if [ $? -eq 0 ]; then
        echo "✓ $1 completed successfully"
    else
        echo "✗ Error: $1 failed"
        exit 1
    fi
}

# Step 1: Install dependencies
echo "Step 1: Installing dependencies..."
composer require webklex/laravel-imap
check_status "Composer dependencies installation"

# Step 2: Run migrations
echo ""
echo "Step 2: Running migrations..."
php artisan migrate
check_status "Database migrations"

# Step 3: Clear caches
echo ""
echo "Step 3: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
check_status "Cache clearing"

# Step 4: Run email setup command
echo ""
echo "Step 4: Running email system setup..."
php artisan email:setup
check_status "Email system setup"

# Step 5: Create log directory
echo ""
echo "Step 5: Setting up log directory..."
mkdir -p storage/logs/email
chmod 755 storage/logs/email
check_status "Log directory setup"

# Step 6: Set permissions
echo ""
echo "Step 6: Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
check_status "Permissions setup"

# Step 7: Generate application key if needed
if ! grep -q "APP_KEY=base64:" .env; then
    echo ""
    echo "Step 7: Generating application key..."
    php artisan key:generate
    check_status "Application key generation"
fi

# Step 8: Show environment variables to configure
echo ""
echo "=========================================="
echo "   Setup Complete!"
echo "=========================================="
echo ""
echo "Please configure the following in your .env file:"
echo ""
echo "# Email Configuration"
echo "MAIL_MAILER=smtp"
echo "MAIL_HOST=your-smtp-host"
echo "MAIL_PORT=587"
echo "MAIL_USERNAME=your-email@example.com"
echo "MAIL_PASSWORD=your-password"
echo "MAIL_ENCRYPTION=tls"
echo "MAIL_FROM_ADDRESS=your-email@example.com"
echo "MAIL_FROM_NAME=\"\${APP_NAME}\""
echo ""
echo "# Zima Email Settings (if using Zima)"
echo "ZIMA_EMAIL_USERNAME=your-email@zima.co.tz"
echo "ZIMA_EMAIL_PASSWORD=your-password"
echo ""
echo "# Optional: AI Service for Smart Compose"
echo "OPENAI_API_KEY=your-openai-api-key"
echo ""
echo "=========================================="
echo ""
echo "To start using the email system:"
echo "1. Configure the environment variables above"
echo "2. Run 'php artisan serve' to start the application"
echo "3. Set up cron job for scheduled tasks:"
echo "   * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "For more information, see docs/EMAIL_SYSTEM.md"
echo ""