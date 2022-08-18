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
	$bookings = SeatregBookingRepository::getBookingsById($bookingId);
	$bookingData = SeatregBookingRepository::getDataRelatedToBooking($bookingId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="icon" href="<?php echo get_site_icon_url(); ?>" />
	<title>
		<?php esc_html_e('Booking check', 'seatreg'); ?>
	</title>
	<style>
		.payment-forms {
			display: flex;
			align-items: center;
			flex-wrap: wrap;
			gap: 12px;
		}
		.payment-forms form {
			
		}
		.payment-instructions {
			margin: 24px 0 12px;
		}
	</style>
	<?php wp_head(); ?>
</head>
<body>
	<?php
		seatreg_echo_booking($registrationId, $bookingId);

		if($bookingData->send_approved_booking_email === '1' && $bookings[0]->status === '2' ) {
			esc_html_e('Did not receive booking receipt? Click the button to send it again.', 'seatreg');
			echo ' <button id="send-receipt" data-booking-id="'. $bookingId .'" data-registration-id="'. $registrationId .'">'. __('Send again', 'seatreg') .'</button><br>';
		}

		if( ($bookingData->paypal_payments === '1' || $bookingData->stripe_payments === '1') && $bookingData->payment_status === null ) {
			$bookingTotalCost = SeatregBookingService::getBookingTotalCost($bookingId, $bookingData->registration_layout);

			if( $bookingTotalCost > 0 ) {
				?>
					<p class="payment-instructions"><?php esc_html_e('Pay for your booking', 'seatreg'); ?></p>
					<div class="payment-forms">
				<?php
			}
			
			if( $bookingData->paypal_payments === '1' && $bookingTotalCost > 0 ) {
				$payPalFromAction = $bookingData->paypal_sandbox_mode === '1' ? SEATREG_PAYPAL_FORM_ACTION_SANDBOX : SEATREG_PAYPAL_FORM_ACTION;
				$returnUrl = SEATREG_PAYPAL_RETURN_URL . '&id=' . $bookingId;
				$cancelUrl = SEATREG_PAYPAL_CANCEL_URL . '&registration=' . $registrationId . '&id=' . $bookingId;
			
				echo SeatregPaymentService::generatePayPalPayNowForm(
					$payPalFromAction, 
					$bookingData,
					$bookingTotalCost,
					$returnUrl,
					$cancelUrl,
					SEATREG_PAYPAL_NOTIFY_URL,
					$bookingId
				);
			}
				
			if( $bookingData->stripe_payments === '1' && $bookingTotalCost > 0 ) {
				echo SeatregPaymentService::generateStripeCheckoutForm($bookingId);
			}
			
			?>
				</div>
			<?php
			
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
