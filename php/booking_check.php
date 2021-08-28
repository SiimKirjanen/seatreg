<?php
	//===========
	/* client may want to check if her booking exists */
	//===========

	if ( ! defined( 'ABSPATH' ) ) {
		exit(); 
	}

	if( empty($_GET['registration']) || empty($_GET['id']) ) {
		exit('Missing data'); 
	}

	require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_functions.php' );

	$bookingId = sanitize_text_field($_GET['id']);
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
</head>
<body>
	<?php
		seatreg_echo_booking(sanitize_text_field($_GET['registration']), $bookingId);

		if($bookingData->paypal_payments === '1' && $bookingData->payment_status === null) {
			$bookingTotalCost = seatreg_get_booking_total_cost($bookingId, $bookingData->registration_layout);
			$payPalFromAction = $bookingData->paypal_sandbox_mode === '1' ? SEATREG_PAYPAL_FORM_ACTION_SANDBOX : SEATREG_PAYPAL_FORM_ACTION;
			$returnUrl = get_site_url() . '?seatreg=payment-return&id=' . $_GET['id'];
			$cancelUrl = get_site_url() . '?seatreg=booking-status&registration=' . $_GET['registration'] . '&id=' . $_GET['id'];
			//$notifyUrl = get_site_url() . '?seatreg=paypal-ipn';
			$notifyUrl = 'https://1802-88-196-42-114.ngrok.io' . '?seatreg=paypal-ipn';
			
			if($bookingTotalCost > 0) {
				echo seatreg_generate_paypal_paynow_form(
					$payPalFromAction, 
					$bookingData->paypal_business_email, 
					$bookingData->paypal_button_id,
					$bookingTotalCost,
					$bookingData->paypal_currency_code,
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
		}
	?>
</body>
</html>
