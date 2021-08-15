<?php
	//===========
	/* PayPal IPN */
	//===========

	if ( ! defined( 'ABSPATH' ) ) {
		exit(); 
	}

	if( empty($_GET['registration']) || empty($_GET['id']) ) {
		exit('Missing data'); 
	}

	require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_functions.php' );
    require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/SeatregPayPalIpn.php' );

	$bookingId = sanitize_text_field($_GET['id']);
	$bookingData = seatreg_get_data_related_to_booking($bookingId);
	$bookingTotalCost = seatreg_get_booking_total_cost($bookingId, $bookingData->registration_layout);
    $payPalIPN = new SeatregPayPalIpn(
		$bookingData->paypal_sandbox_mode === '1',
		$bookingData->paypal_business_email,
		$bookingData->paypal_currency_code,
		$bookingTotalCost,
		$bookingId
	);
	$payPalIPN->run();

	header("HTTP/1.1 200 OK");