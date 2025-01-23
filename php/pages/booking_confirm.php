<?php

	//=================================
	/* Booking confirm page */
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
	<link rel="icon" href="<?php echo esc_url(get_site_icon_url()); ?>" />
	<title>
		<?php esc_html_e('Booking confirm', 'seatreg'); ?>
	</title>
	<style>
		.page-wrap {
			text-align: center;
		}
	</style>
	<?php wp_head(); ?>
</head>
<body>
	<div class="page-wrap">
		<?php
			$validator = new SeatregConfirmBooking(sanitize_text_field($_GET['confirmation-code']));
			$validator->startConfirm();
		?>
	</div>
</body>
</html>