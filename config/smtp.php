<?php

/**
 * Gmail SMTP Configuration for PHPMailer
 * 
 * SETUP INSTRUCTIONS:
 * 1. Go to https://myaccount.google.com/security
 * 2. Enable 2-Step Verification (required for App Passwords)
 * 3. Go to https://myaccount.google.com/apppasswords
 * 4. Generate a new App Password (select "Mail" and "Other")
 * 5. Copy the 16-character password below
 * 
 * Your Gmail address goes in SMTP_USER
 * Your App Password goes in SMTP_PASS (NOT your regular Gmail password)
 */

$SMTP_HOST     = 'smtp.gmail.com';
$SMTP_PORT     = 587;
$SMTP_USERNAME = 'your-email@gmail.com';   // <-- Your Gmail address
$SMTP_PASSWORD = 'xxxx-xxxx-xxxx-xxxx';   // <-- Your Gmail App Password
$SMTP_FROM     = 'your-email@gmail.com';   // <-- Same as SMTP_USERNAME
$SMTP_FROM_NAME = 'PROMASY';
$SMTP_ENCRYPTION = 'tls';                 // Use 'tls' for port 587

?>
