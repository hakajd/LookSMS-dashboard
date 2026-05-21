<?php
define('DB_SERVER', getenv('DB_HOST') ?: 'db');
define('DB_USER',   getenv('DB_USER') ?: '');
define('DB_PASS',   getenv('DB_PASS') ?: '');
define('DB_NAME',   getenv('DB_NAME') ?: 'looksms_app');
define('TIMEZONE',  getenv('APP_TIMEZONE') ?: 'Europe/Brussels');
define('APP_SECRET_KEY',  getenv('APP_SECRET_KEY') ?: '');
define('APP_SESSION_NAME', 'LOOKSMS');
