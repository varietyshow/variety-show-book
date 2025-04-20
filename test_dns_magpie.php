<?php
// Suppress deprecated warnings for this test
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

$host = 'sandbox.api.magpie.im';
$ip = gethostbyname($host);

if ($ip === $host) {
    echo "DNS lookup failed for $host\n";
} else {
    echo "$host resolves to $ip\n";
    // Try to open a socket connection to port 443 (HTTPS)
    $fp = fsockopen($host, 443, $errno, $errstr, 5);
    if ($fp) {
        echo "Successfully connected to $host:443\n";
        fclose($fp);
    } else {
        echo "Could not connect to $host:443 - $errstr ($errno)\n";
    }
}
