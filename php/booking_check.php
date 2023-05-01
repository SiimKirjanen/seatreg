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
		.custom-payment-box {
			cursor: pointer;
			min-width: 230px;
			height: 56px;
			line-height: 56px;
			margin-top: 5px;
			text-align: center;
			border: 2px solid #b7b7b7;
			border-radius: 6px;
			font-size: 18px;
		}
		.payment-form-footer {
			margin-top: 12px;
			font-size: 18px;
			font-weight: 600;
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

		if( SeatregPaymentRepository::hasPaymentEnabled($bookingData) && $bookingData->payment_status === null ) {
			$bookingTotalCost = SeatregBookingService::getBookingTotalCost($bookingId, $bookingData->registration_layout);
			$bookingHasCost = $bookingTotalCost > 0;

			if( $bookingHasCost ) {
				?>
					<p class="payment-instructions"><?php esc_html_e('Pay for your booking', 'seatreg'); ?></p>
					<div class="payment-forms">
				<?php
			}
			
			if( $bookingData->paypal_payments === '1' && $bookingHasCost ) {
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
				
			if( $bookingData->stripe_payments === '1' && $bookingHasCost ) {
				echo SeatregPaymentService::generateStripeCheckoutForm($bookingId);
			}

			if( $bookingData->custom_payment === '1' && $bookingHasCost ) {
				echo SeatregPaymentService::generateCustomPaymentButton($bookingData);
			}
			
			?>
				</div>
				<div class="payment-form-footer" style="display: none">
					<?php echo $bookingData->custom_payment_description; ?>
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
