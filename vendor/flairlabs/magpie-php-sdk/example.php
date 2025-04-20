<?php

require_once 'vendor/autoload.php';

// For production
$pkLiveKey = '';
$skLiveKey = '';
// For sandbox
$pkTestKey = 'pk_test_aBTnnTX5QaO2AblZ5wNq2A';
$skTestKey = 'sk_test_DneFEolPcJVCKfQMzDjhWQ';

// Note, if you pass 'v1.1' as the last parameter, the charges endpoint
// will use v1.1 endpoint

$magpie = new MagpieApi\Magpie($pkTestKey, $skTestKey, true);

// Create token
// $response = $magpie->token->create(
// 	'Rhiza Talavera',
// 	'4111111111111111',
// 	12,
// 	2019,
// 	143
// );

/**
 * NOTE for Magpie\Response
 *
 * To get the response converted to array, call $response->toArray()
 * To check if the response is successful, call $response->isSuccess()
 * To get the http code of the response, call $response->httpCode()
 * To get the raw output of the response, call $response->raw()
 */

// Retrieve token
// $response = $magpie->token->get('tok_MTQ3MNzNiZDMA4MTFj');

// Create Charge
// $response = $magpie->charge->create(
// 	50000,
// 	'php',
// 	'tok_MTQ3MNzNiZDMA4MTFj',
// 	'A short description of the charge',
// 	'Sandbox BV Netherlands',
// 	false
// );

// Retrieve Charge
// $response = $magpie->charge->get('ch_MTQ3MNjk1MzlWEwMGEz');

// Capture a charge
// $response = $magpie->charge->capture('ch_MTQ3MMGMzZWVjM2MDEx', 5000);

// Void a charge
// $response = $magpie->charge->void('ch_MTQ3MMGMzZWVjM2MDEx');

// Refund Charge
// $response = $magpie->charge->refund('ch_MTQ3MMGMzZWVjM2MDEx', 5000);

// Create Customer
// $response = $magpie->customer->create('jaimehing3@gmail.com', 'test desc');

// Get Customer
// $response = $magpie->customer->get('cus_MTQ3MyMzk3MJiOWZi');

// Update Customer
// $response = $magpie->customer->update(
// 	'cus_MTQ3ME4MWEwY2Mx',
// 	'tok_MTQ3MNzNiZDMA4MTFj'
// );

// Delete Customer
// $response = $magpie->customer->delete('cus_MTQ3MyMzk3MJiOWZi'); 

// Delete Customer Source
// $response = $magpie->customer->deleteSource(
// 	'cus_MTQ3ME4MWEwY2Mx',
// 	'card_MTQ3MQ0YzlMzgy'
// ); 
