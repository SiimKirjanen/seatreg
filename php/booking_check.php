<?php
	//===========
	/*client may want to check if her booking exists*/
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

		if($bookingData->paypal_payments === '1') {
			$bookingTotalCost = seatreg_get_booking_total_cost($bookingId, $bookingData->registration_layout);
			$payPalFromAction = SEATREG_PAYPAL_FORM_ACTION_SANDBOX;

			echo seatreg_generate_paypal_paynow_form(
				$payPalFromAction, 
				$bookingData->paypal_business_email, 
				$bookingData->paypal_button_id,
				$bookingTotalCost,
				$bookingData->paypal_currency_code
			);
		}
	?>
</body>
</html>
