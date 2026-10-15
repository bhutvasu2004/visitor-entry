<?php
// Settings you may want to change. Keep this file out of the public web folder.
return [
    // Database (using the existing "student" database)
    'db_host' => 'localhost',
    'db_name' => 'student',
    'db_user' => 'student',
    'db_pass' => 'student',

    // Your time zone, e.g. 'Asia/Kolkata', 'Europe/London', 'America/New_York'
    'timezone' => 'Asia/Kolkata',

    // Address phones will use to reach this PC, e.g. 'http://192.168.1.50:8000'
    // Leave empty to auto-detect from the address you used to open the QR page.
    'base_url' => '',

    // Used for the WhatsApp button when a visitor types a number without a country code.
    'default_country_code' => '91',

    // Where photos are stored (outside the public web folder on purpose).
    'photo_dir' => __DIR__ . '/../storage/photos',

    // Photo limits
    'max_photo_bytes' => 8 * 1024 * 1024,

    // Spam protection: max registrations per phone/device (IP) in 10 minutes
    'max_registrations_per_10min' => 10,

    // Login protection: failed logins allowed per IP in 15 minutes
    'max_login_failures' => 5,
];