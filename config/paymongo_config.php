<?php
// PayMongo API configuration
// These are test keys that can be used for development and testing

// Secret key - used for server-side API calls
define('PAYMONGO_SECRET_KEY', 'sk_test_oKNrMmf4e3WAgzuWFHL25uhN');

// Publishable key - used for client-side API calls
define('PAYMONGO_PUBLISHABLE_KEY', 'pk_test_pbxxXMZ1aJDYQGnCH1XuFm9n');


// API Base URL
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');

// Success and failure redirect URLs
define('PAYMONGO_SUCCESS_URL', 'https://variety-show-book-2.onrender.com/customer/payment-confirmation.php?status=success');
define('PAYMONGO_FAILED_URL', 'https://variety-show-book-2.onrender.com/customer/payment-confirmation.php?status=failed');
