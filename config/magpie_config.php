<?php
// Magpie API configuration
// Fill in your actual Magpie secret key and redirect URLs

define('MAGPIE_SECRET_KEY', 'sk_test_DKW88HTN57V9Ae4UX50NpT'); // TODO: Replace with your real secret key

define('MAGPIE_SUCCESS_URL', 'https://variety-show-book-2.onrender.com/customer/payment-confirmation.php?status=success');

// Add your Magpie publishable key here
// Example: define('MAGPIE_PUBLISHABLE_KEY', 'pk_test_xxxxxxxxxxxxxxxxxxxxx');
define('MAGPIE_PUBLISHABLE_KEY', 'pk_test_R5Vw8uN8dIDW2JrMLaGoYZ'); // TODO: Replace with your real publishable key

define('MAGPIE_FAILED_URL', 'https://variety-show-book-2.onrender.com/customer/payment-confirmation.php?status=failed');
