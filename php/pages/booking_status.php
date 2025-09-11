<?php
	//===========
	/* Booking status page */
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
	$customPayments = $bookingData->custom_payments ? json_decode( $bookingData->custom_payments ) : [];
	$couponsEnabled = SeatregCouponRepository::areCouponsEnabled($registrationId);
	$appliedCoupon = SeatregCouponRepository::getBookingAppliedCoupon($bookingId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="icon" href="<?php echo esc_url(get_site_icon_url()); ?>" />
	<title>
		<?php esc_html_e('Booking status', 'seatreg'); ?>
	</title>
	<style>
		.page-wrap {
			text-align: center;
		}
		.page-wrap table {
			margin: 0 auto;
		}
		.payment-forms {
			display: flex;
			align-items: center;
			justify-content: center;
			flex-wrap: wrap;
			gap: 12px;
		}
		.payment-instructions {
			margin: 24px 0 16px;
		}
		.custom-payment-box {
			display: flex;
			align-items: center;
			gap: 8px;
			cursor: pointer;
			min-width: 230px;
			height: 56px;
			padding: 0 6px;
			line-height: 56px;
			margin-top: 5px;
			text-align: center;
			border: 2px solid #b7b7b7;
			border-radius: 6px;
			font-size: 18px;
		}
		.custom-payment-box__img {
			height: 47px;
		}
		.custom-payment-box__text {
			flex-grow: 1;
		}
		#custom-payment-descriptions {
			margin-top: 12px;
			font-size: 18px;
			font-weight: 600;
		}
	</style>
	<?php wp_head(); ?>
</head>
<body>
	<div class="page-wrap">
		<?php
			seatreg_echo_booking($registrationId, $bookingId);

			if( SeatregOptionsRepository::shouldAllowPdfGeneration($bookings, $bookingData) ) {
				$pdfFileUrl = get_site_url() . '/?seatreg=booking-pdf&id=' . $bookingId;

				?>
				<div style="margin-bottom: 6px;">
					<a href="<?php echo esc_url($pdfFileUrl); ?>" target="_blank">
						<img width="60" src="<?php echo esc_url(SEATREG_PLUGIN_FOLDER_URL) . '/img/pdf_logo.png'; ?>" alt="PDF" />
					</a>
				</div>
				<?php
			}

			if( $bookingData && $bookingData->payment_text ) {
				echo '<h3>', esc_html__('Payment info', 'seatreg'), '</h3>';
				echo '<p>', nl2br(esc_html($bookingData->payment_text)) ,'</p>';
			}

			if($bookingData->send_approved_booking_email === '1' && $bookings[0]->status === '2' ) {
				esc_html_e('Did not receive booking receipt? Click the button to send it again.', 'seatreg');
				echo ' <button id="send-receipt" data-booking-id="'. esc_html($bookingId) .'" data-registration-id="'. esc_html($registrationId) .'">'. esc_html__('Send again', 'seatreg') .'</button><br>';
			}

			if( SeatregPaymentRepository::hasPaymentEnabled($bookingData) && ($bookingData->payment_status === null || $bookingData->payment_status === SEATREG_PAYMENT_NONE) ) {
				$bookingTotalCost = SeatregBookingService::getBookingTotalCost($bookingId, $bookingData->registration_layout);
				if ( $couponsEnabled && $appliedCoupon ) {
					$bookingTotalCost = SeatregBookingService::applyCouponDiscountToTotalCost($bookingTotalCost, $appliedCoupon);
				}
				$bookingHasCost = $bookingTotalCost > 0;
				$legacyCustomPaymentId = "legacy-custom-payment-id";

				if( $bookingHasCost ) {
					?>
						<p class="payment-instructions"><?php esc_html_e('Pay for your booking using the following methods', 'seatreg'); ?></p>
						<div class="payment-forms">
					<?php
				}
				
				if( $bookingData->paypal_payments === '1' && $bookingHasCost ) {
					$payPalFromAction = $bookingData->paypal_sandbox_mode === '1' ? SEATREG_PAYPAL_FORM_ACTION_SANDBOX : SEATREG_PAYPAL_FORM_ACTION;
					$returnUrl = SEATREG_PAYPAL_RETURN_URL . '&id=' . esc_html($bookingId);
					$cancelUrl = SEATREG_PAYPAL_CANCEL_URL . '&registration=' . esc_html($registrationId) . '&id=' . esc_html($bookingId);
				
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
					echo SeatregPaymentService::generateCustomPaymentButton($bookingData->custom_payment_title, $legacyCustomPaymentId, $registrationId);
				}

				if( $bookingHasCost ) {
					foreach($customPayments as $customPayment) {
						echo SeatregPaymentService::generateCustomPaymentButton($customPayment->title, $customPayment->paymentId, $registrationId, $customPayment->paymentIcon);
					}
				}
				
				?>
					</div>
					<div id="custom-payment-descriptions">

						<?php if($bookingData->custom_payment_description) : ?>
							<div data-payment-id="<?php echo esc_attr($legacyCustomPaymentId); ?>" style="display: none">
								<?php echo esc_html($bookingData->custom_payment_description); ?>
							</div>
						<?php endif; ?>

						<?php foreach($customPayments as $customPayment): ?>
							<div data-payment-id="<?php echo esc_attr($customPayment->paymentId); ?>" style="display: none">
								<?php echo esc_html($customPayment->description); ?>
							</div>
						<?php endforeach; ?>

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
	</div>
	<?php wp_footer(); ?>
</body>
</html>
