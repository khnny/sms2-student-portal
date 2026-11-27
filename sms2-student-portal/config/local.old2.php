<?php
// Laptop (XAMPP) only: point SMS2 + CRAD at the fresh database sms2_v3.
// The old sms2_db is left untouched.
define('DB_CONNECTION', 'mysql');
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'sms2_v3');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('CRAD_DB_NAME', 'sms2_v3');
define('STUDENT_PORTAL_DB_NAME', 'sms2_v3');
define('REPORTS_DB_NAME', 'sms2_v3');
define('USERMGMT_DB_NAME', 'sms2_v3');
