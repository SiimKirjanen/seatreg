<?php
	//===========
	/* PayPal IPN */
	//===========

	if ( ! defined( 'ABSPATH' ) ) {
		exit(); 
	}

	if( empty($_POST['custom']) ) {
		exit('Missing data'); 
	}

	require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_functions.php' );
    require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/SeatregPayPalIpn.php' );

	$bookingId = sanitize_text_field($_POST['custom']);

	if( !seatreg_get_bookings_by_booking_id( $bookingId )) {
		exit('Booking not found'); 
	}

	$bookingData = seatreg_get_data_related_to_booking($bookingId);
	$bookingTotalCost = seatreg_get_booking_total_cost($bookingId, $bookingData->registration_layout);
    $payPalIPN = new SeatregPayPalIpn(
		$bookingData->paypal_sandbox_mode === '1',
		$bookingData->paypal_business_email,
		$bookingData->paypal_currency_code,
		$bookingTotalCost,
		$bookingId,
		$bookingData->payment_completed_set_booking_confirmed,
		$bookingData->registration_code
	);
	$payPalIPN->run();

	header("HTTP/1.1 200 OK");