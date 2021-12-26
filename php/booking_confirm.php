<?php

	//=================================
	/* Booking confirm */
	//=================================

	if ( ! defined( 'ABSPATH' ) ) {
		exit(); 
	}

	if( empty($_GET['confirmation-code']) ) {
		exit('Missing data');
	}

	require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/SeatregConfirmBooking.php' );
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="icon" href="<?php echo get_site_icon_url(); ?>" />
	<title>
		<?php esc_html_e('Booking confirm', 'seatreg'); ?>
	</title>
</head>
<body>
	<div>
		<?php
			$validator = new SeatregConfirmBooking(sanitize_text_field($_GET['confirmation-code']));
			$validator->startConfirm();
		?>
	</div>
</body>
</html>