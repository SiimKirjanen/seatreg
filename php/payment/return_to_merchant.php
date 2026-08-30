<?php
	//===========
	/* Return to Merchant page from PayPal */
	//===========

	if ( ! defined( 'ABSPATH' ) ) {
		exit();
	}

	if( empty($_GET['id']) ) {
		exit('Missing data');
	}

	require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_functions.php' );

	$bookingId = sanitize_text_field($_GET['id']);
	$bookingData = SeatregBookingRepository::getDataRelatedToBooking($bookingId);

	if( !$bookingData ) {
		exit('Missing data');
	}

	$paymentStatus = $bookingData->payment_status;
	$paymentInProgress = $paymentStatus === null || $paymentStatus === SEATREG_PAYMENT_PROCESSING;

	if($paymentStatus === null) {
		SeatregPaymentService::insertProcessingPayment($bookingId);
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="<?php echo esc_url(get_site_icon_url()); ?>" />
	<title>
		<?php esc_html_e('Payment processing', 'seatreg'); ?>
	</title>
	<?php wp_head(); ?>
</head>
<body>
	<?php
		SeatregPublicPageService::renderPageStart(array(
			'title' => $paymentInProgress ? __('Thank you for your payment', 'seatreg') : __('Payment already processed', 'seatreg'),
			'name' => wp_unslash($bookingData->registration_name),
			'logoId' => $bookingData->page_logo,
		));

		if($paymentInProgress)  {
			esc_html_e('Your payment is being processed', 'seatreg');
		}else{
			esc_html_e('Payment is already processed or doe\'s not exist. For more information check your booking status page.', 'seatreg');
		}

		if( $bookingData->payment_return_page_text ) {
			echo '<div style="margin-top: 12px;">', wp_kses_post($bookingData->payment_return_page_text), '</div>';
		}

		SeatregPublicPageService::renderPageEnd();
	?>

	<?php wp_footer(); ?>
</body>
</html>
