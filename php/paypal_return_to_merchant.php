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
	$paymentStatus = $bookingData->payment_status;

	if($paymentStatus === null) {
		SeatregPaymentService::insertProcessingPayment($bookingId);
	}	
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="icon" href="<?php echo get_site_icon_url(); ?>" />
	<title>
		<?php esc_html_e('Payment processing', 'seatreg'); ?>
	</title>
</head>
<body>

	<?php 
		if($paymentStatus === null || $paymentStatus === SEATREG_PAYMENT_PROCESSING)  {
			esc_html_e('Your payment is being processed', 'seatreg'); 
		}else{
			esc_html_e('Payment is already processed or doe\'s not exist. For more information check your booking status page.', 'seatreg'); 
		}
	?>
</body>
</html>
