# Billing Statement Automatic Generation

## Overview

The system automatically generates billing statements for recurring subscriptions that are expiring within 5 days. This happens automatically every day without any admin intervention.

## How It Works

### Automatic Generation (Production)

The system uses Laravel's task scheduler to automatically run billing generation daily:

1. **Scheduled Time**: Every day at 1:00 AM
2. **What it does**:
   - Finds all active recurring subscriptions expiring within 5 days
   - Creates billing statements for each subscription
   - Creates pending payments with payment codes
   - Generates invoices automatically
   - Sends email notifications to users
   - Prevents duplicate statements

### Setup for Production

Add this to your server's crontab (runs every minute, Laravel handles the scheduling):

```bash
* * * * * cd /path-to-your-project/backend && php artisan schedule:run >> /dev/null 2>&1
```

Or for Windows Task Scheduler:
- Create a task that runs every minute
- Command: `php artisan schedule:run`
- Working directory: `C:\path-to-your-project\backend`

### Testing the Scheduler

#### Option 1: Test the billing command directly
```bash
cd backend
php artisan billing:generate-statements
```

#### Option 2: Test the full scheduler
```bash
cd backend
php artisan schedule:run
```

This will run all scheduled tasks that are due to run now.

#### Option 3: List scheduled tasks
```bash
cd backend
php artisan schedule:list
```

### Manual Commands Available

1. **Generate billing statements manually**:
   ```bash
   php artisan billing:generate-statements
   ```

2. **Update expired subscriptions**:
   ```bash
   php artisan subscriptions:update-expired
   ```

3. **Cancel old pending payments**:
   ```bash
   php artisan payments:cancel-old
   ```

## Scheduled Tasks

The following tasks run automatically:

1. **Update Expired Subscriptions** - Daily at 12:00 AM
   - Marks subscriptions as EXPIRED when end_date has passed

2. **Process Recurring Billing** - Daily at 1:00 AM
   - Generates billing statements for subscriptions expiring in 5 days
   - Creates payments and invoices
   - Sends notifications

3. **Cancel Old Payments** - Daily at 2:00 AM
   - Cancels pending payments older than 15 days

## Test Account

After running `php artisan db:seed`, you can test billing with:

- **Email**: `billing@gmail.com`
- **Password**: `password`

This account has:
- Active subscription expiring in 3 days (will auto-generate billing)
- 1 PENDING billing statement with invoice
- 1 PAID billing statement with invoice and receipt

## Verification

To verify automatic generation is working:

1. Check logs: `storage/logs/laravel.log`
2. Check database: `billing_statements` table
3. Check user notifications (if email is configured)
4. Run `php artisan schedule:list` to see all scheduled tasks

## Troubleshooting

If billing statements aren't being generated automatically:

1. **Check if cron is running**:
   ```bash
   php artisan schedule:list
   ```

2. **Test manually**:
   ```bash
   php artisan billing:generate-statements
   ```

3. **Check logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Verify queue is working** (if using queue):
   ```bash
   php artisan queue:work
   ```

## Notes

- The automatic generation only creates statements for subscriptions expiring within 5 days
- Duplicate statements are prevented (checks for existing PENDING statements)
- Manual generation via admin UI is still available for special cases
- All operations are logged for debugging

