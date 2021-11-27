<?php
	//===========
	/* client may want to check booking status */
	//===========

	if ( ! defined( 'ABSPATH' ) ) {
		exit(); 
	}

	if( empty($_GET['registration']) || empty($_GET['id']) ) {
		exit('Missing data'); 
	}

	$bookingId = sanitize_text_field($_GET['id']);
	$registrationId = sanitize_text_field($_GET['registration']);
	$bookings = SeatregBookingRepository::getBookings($bookingId);
	$bookingData = seatreg_get_data_related_to_booking($bookingId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="icon" href="<?php echo get_site_icon_url(); ?>" />
	<title>
		<?php esc_html_e('Booking check', 'seatreg'); ?>
	</title>
	<?php wp_head(); ?>
</head>
<body>
	<?php
		seatreg_echo_booking($registrationId, $bookingId);

		if($bookingData->send_approved_booking_email === '1' && $bookings[0]->status === '2' ) {
			esc_html_e('Did not receive booking receipt? Click the button to send it again.', 'seatreg');
			echo ' <button id="send-receipt" data-booking-id="'. $bookingId .'" data-registration-id="'. $registrationId .'">'. __('Send again', 'seatreg') .'</button>';
		}

		if($bookingData->paypal_payments === '1' && $bookingData->payment_status === null) {
			$bookingTotalCost = seatreg_get_booking_total_cost($bookingId, $bookingData->registration_layout);
			$payPalFromAction = $bookingData->paypal_sandbox_mode === '1' ? SEATREG_PAYPAL_FORM_ACTION_SANDBOX : SEATREG_PAYPAL_FORM_ACTION;
			$siteUrl = get_site_url(); 
			$returnUrl = $siteUrl . '?seatreg=payment-return&id=' . $bookingId;
			$cancelUrl = $siteUrl . '?seatreg=booking-status&registration=' . $registrationId . '&id=' . $bookingId;
			$notifyUrl = $siteUrl . '?seatreg=paypal-ipn';
			
			if($bookingTotalCost > 0) {
				echo seatreg_generate_paypal_paynow_form(
					$payPalFromAction, 
					$bookingData,
					$bookingTotalCost,
					$returnUrl,
					$cancelUrl,
					$notifyUrl,
					$bookingId
				);
			}
		}else if($bookingData->payment_status === SEATREG_PAYMENT_PROCESSING) {
			esc_html_e('Your payment is being processed', 'seatreg');
		}else if($bookingData->payment_status === SEATREG_PAYMENT_COMPLETED) {
			esc_html_e('Your payment is completed', 'seatreg');
		}else if($bookingData->payment_status === SEATREG_PAYMENT_VALIDATION_FAILED){
			esc_html_e('There seems to be a problem with your payment. Please notify site administrator.', 'seatreg');
		}else if($bookingData->payment_status === SEATREG_PAYMENT_REFUNDED) {
			esc_html_e('You payment has been refunded', 'seatreg');
		}else if($bookingData->payment_status === SEATREG_PAYMENT_REVERSED) {
			esc_html_e('You payment has been reversed', 'seatreg');
		}
	?>
	<?php wp_footer(); ?>	
</body>
</html>
