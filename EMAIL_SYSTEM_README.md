# SACCOS Email System

A comprehensive email management system integrated into the SACCOS Laravel application, providing Gmail-like functionality with advanced features.

## Features

### Core Features
- ✅ **Compose and Send Emails** - Rich text editor with formatting
- ✅ **Folder Management** - Inbox, Sent, Drafts, Spam, Trash, Snoozed, Scheduled
- ✅ **Email Actions** - Reply, Reply All, Forward, Delete, Mark as Read/Unread
- ✅ **Search Functionality** - Basic and advanced search with filters
- ✅ **Spam Detection** - Automatic spam filtering
- ✅ **Rate Limiting** - Prevents email flooding (5/min, 50/hour)
- ✅ **Email Encryption** - Secure storage of email content
- ✅ **Storage Quota** - Track and display email storage usage

### Advanced Features
- ✅ **Smart Compose** - AI-powered email composition suggestions
- ✅ **Email Snooze** - Temporarily hide emails until specified time
- ✅ **Schedule Send** - Write now, send later automatically
- ✅ **Undo Send** - Cancel emails within 30 seconds of sending
- ✅ **SMTP Integration** - Real email sending via SMTP
- ✅ **IMAP Integration** - Sync emails from external servers
- ✅ **Advanced Search** - Filter by sender, recipient, date, attachments
- ✅ **Activity Logging** - Track all email actions for audit
- ✅ **Automated Backup** - Archive old emails automatically

### Recently Added Features
- ✅ **Email Threading** - Conversation view groups related emails
- ✅ **Pin Emails** - Pin important emails to the top
- ✅ **Flag Emails** - Flag emails for follow-up

### Planned Features
- 📋 Email Templates - Save and reuse email templates
- 📋 Email Signatures - Multiple signatures management
- 📋 Email Tracking - Read receipts and link tracking
- 📋 Follow-up Reminders - Set reminders for email responses
- 📋 Labels & Categories - Organize emails with custom labels
- 📋 File Attachments - Attach files to emails
- 📋 Contact Management - Built-in address book
- 📋 Rules & Filters - Automated email sorting
- 📋 Focused Inbox - AI-powered inbox prioritization
- 📋 Sweep - Bulk email actions
- 📋 Quick Steps - Multi-action sequences
- 📋 Suggested Replies - AI-powered quick responses

## Quick Start

### Installation

1. **Run the setup script:**
   ```bash
   ./setup-email-system.sh
   ```

   Or manually:
   ```bash
   # Install dependencies
   composer require webklex/laravel-imap
   
   # Run migrations
   php artisan migrate
   
   # Run setup command
   php artisan email:setup
   ```

2. **Configure environment variables in `.env`:**
   ```env
   # SMTP Settings
   MAIL_MAILER=smtp
   MAIL_HOST=server354.web-hosting.com
   MAIL_PORT=465
   MAIL_USERNAME=your-email@example.com
   MAIL_PASSWORD=your-password
   MAIL_ENCRYPTION=ssl
   MAIL_FROM_ADDRESS=your-email@example.com
   MAIL_FROM_NAME="${APP_NAME}"
   
   # Email System Settings
   EMAIL_SERVER=zima
   EMAIL_SYNC_ENABLED=true
   EMAIL_SYNC_INTERVAL=5
   EMAIL_SYNC_BATCH_SIZE=50
   EMAIL_SYNC_DAYS=30
   
   # Optional: AI for Smart Compose
   OPENAI_API_KEY=your-api-key
   ```

3. **Set up cron for scheduled tasks:**
   ```bash
   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
   ```

## Usage

### Accessing the Email System
Navigate to `/email` in your application or access through the main menu.

### Sending Emails
1. Click "Compose" button
2. Fill in recipient, subject, and message
3. Options:
   - **Send Now** - Send immediately with 30-second undo window
   - **Schedule** - Pick a time to send later
   - **Save as Draft** - Save for later editing

### Smart Compose
- Start typing and suggestions appear automatically
- Press `Tab` to accept suggestion
- Use `↑↓` arrows to navigate suggestions
- Press `Esc` to dismiss

### Snoozing Emails
1. Click the clock icon on any email
2. Choose preset time or custom date/time
3. Email moves to Snoozed folder
4. Returns to inbox at specified time

### Scheduling Emails
1. Compose email normally
2. Click "Schedule" instead of "Send Now"
3. Choose time from suggestions or pick custom
4. View/cancel in Scheduled folder

### Undo Send
- After sending, a 30-second countdown appears
- Click "Undo" to move email back to drafts
- Email sends automatically after countdown

### Conversation View
- Toggle between conversation and list view using the button in toolbar
- Related emails are grouped by subject and participants
- Shows message count and latest sender
- Click to expand/collapse conversations
- Pinned emails have a yellow ring indicator

### Pin & Flag Emails
- **Pin**: Click the bookmark icon to pin important emails to top
- **Flag**: Click the flag icon to mark emails for follow-up
- Pinned emails stay at the top of the list
- Both features work in conversation and regular views

## Commands

### Email Management
```bash
# Sync emails from IMAP
php artisan emails:sync --all
php artisan emails:sync --email=user@example.com
php artisan emails:sync --user=1

# Process scheduled tasks manually
php artisan emails:process-snoozes
php artisan emails:send-scheduled
php artisan emails:process-undo-queue
```

### Setup and Maintenance
```bash
# Run complete setup
php artisan email:setup

# Run with options
php artisan email:setup --force
php artisan email:setup --skip-migrations
php artisan email:setup --skip-seeder
```

## Architecture

### Database Tables
- `emails` - Main email storage
- `email_snoozes` - Snoozed email tracking
- `scheduled_emails` - Scheduled email queue
- `email_templates` - Email templates
- `email_signatures` - User signatures
- `email_tracking` - Email open/click tracking
- `email_reminders` - Follow-up reminders
- `email_labels` - Custom labels
- `smart_compose_history` - AI suggestion history
- `email_activity_logs` - Audit trail
- `email_archives` - Archived emails

### Services
- `EmailService` - Core email operations
- `SmartComposeService` - AI-powered suggestions
- `EmailSnoozeService` - Snooze functionality
- `ScheduledEmailService` - Schedule send
- `UndoSendService` - Undo send feature
- `ImapService` - IMAP sync

### Components
- `Email` - Main Livewire component
- `EmailDetail` - Email view component

## Configuration

### Rate Limiting
Edit in `app/Services/EmailService.php`:
```php
protected $rateLimitPerMinute = 5;
protected $rateLimitPerHour = 50;
```

### Storage Quota
Edit in `app/Http/Livewire/Email/Email.php`:
```php
public $storageLimit = 15728640; // 15MB
```

### Undo Window
Edit in `app/Services/UndoSendService.php`:
```php
protected $undoWindowSeconds = 30;
```

### Spam Keywords
Edit in `app/Services/EmailService.php` → `detectSpam()` method

## Security

- **Encryption**: All email bodies encrypted at rest
- **CSRF Protection**: Handled by Laravel/Livewire
- **Rate Limiting**: Prevents email flooding
- **Authentication**: User-based access control
- **Activity Logging**: All actions logged for audit

## Troubleshooting

### SMTP Connection Failed
- Verify SMTP credentials in `.env`
- Check firewall for outbound SMTP
- For Gmail, use app-specific password

### Emails Not Syncing
- Check IMAP credentials
- Verify `EMAIL_SYNC_ENABLED=true`
- Run `php artisan emails:sync --all` manually

### Scheduled Tasks Not Running
- Verify cron job is set up
- Check Laravel scheduler is running
- Review logs in `storage/logs/`

### Performance Issues
- Archive old emails: `php artisan tinker` → `(new EmailService())->backupOldEmails(90)`
- Increase sync batch size in `.env`
- Enable query caching

## Support

For issues or questions:
1. Check `docs/EMAIL_SYSTEM.md` for detailed documentation
2. Review logs in `storage/logs/email/`
3. Enable debug mode: `LOG_LEVEL=debug` in `.env`

## License

This email system is part of the SACCOS application and follows the same license terms.