# Email Configuration - Brevo Setup Guide

## Overview
We use Brevo (formerly Sendinblue) for sending password reset emails.
- **Free tier**: 300 emails per day
- **No credit card required** to start
- **Unlimited contacts**

## Setup Instructions

### Step 1: Create Brevo Account
1. Go to https://www.brevo.com/
2. Sign up (free)
3. Verify your email address

### Step 2: Get SMTP Credentials
1. Log in to Brevo
2. Go to **Settings** → **SMTP & API**
3. Copy your **SMTP username** (your email address)
4. Generate or copy your **SMTP password/key**

### Step 3: Configure on Your Server

#### Option A: Using Environment Variables (RECOMMENDED for production)
Add these to your server's environment:
```bash
export EMAIL_HOST="smtp-relay.brevo.com"
export EMAIL_PORT="587"
export EMAIL_USERNAME="your_brevo_email@example.com"
export EMAIL_PASSWORD="your_brevo_smtp_key"
export EMAIL_FROM="noreply@sociaalailab.nl"
```

#### Option B: Direct Configuration
Edit `email_config.php` and update these values:
```php
$emailConfig = [
    'host'     => 'smtp-relay.brevo.com',
    'port'     => 587,
    'username' => 'your_brevo_email@example.com',
    'password' => 'your_brevo_smtp_key',
    'from'     => 'noreply@sociaalailab.nl',
    'from_name' => 'SociaalAI Lab'
];
```

### Step 4: Test Email Sending
Click "Forgot password" on the login page and enter your email address. 
You should receive a password reset email within seconds.

## Troubleshooting

### Emails not sending?
1. **Check credentials** - Verify username and password are correct
2. **Check port** - Make sure port 587 is not blocked by firewall
3. **Check sender** - Verify the 'from' email is valid
4. **Enable SMTP Debug** - In `email_config.php`, change `SMTPDebug` from 0 to 2 for detailed errors

### Common Errors
- **Connection timeout** → Port might be blocked. Contact hosting support.
- **Authentication failed** → Check credentials in Brevo settings
- **Invalid sender** → Email domain must be verified in Brevo

## Scaling Beyond Free Tier
When you exceed 300 emails/day:
- Brevo paid plans start from €20/month for 10,000 emails/month
- Or switch to another provider (AWS SES, SendGrid, etc.)

## Files Modified
- `email_config.php` - New email configuration and helper functions
- `forgot_password.php` - Updated to use Brevo SMTP

## Security Notes
- ✅ Credentials not hardcoded (use environment variables)
- ✅ No sensitive data in version control
- ✅ STARTTLS encryption enabled
- ✅ PHPMailer handles SMTP securely
