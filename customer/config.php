<?php
// config.php
return [
    'db_host' => getenv('DB_HOST') ?: 'sql.freedb.tech 3306',
    'db_user' => getenv('DB_USER') ?: 'freedb_varietyshow',
    'db_pass' => getenv('DB_PASS') ?: 'D!H7nuCsrenD8WM',
    'db_name' => getenv('DB_NAME') ?: 'freedb_db_booking_system'
];
?>
