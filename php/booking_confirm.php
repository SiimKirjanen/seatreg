<?php

	//=================================
	/* Booking confirm */
	//=================================

	if( empty($_GET['confirmation-code']) ) {
		exit();
	}
	
	require_once('./../registration/php/reg_functions.php');
	require_once('./ConfirmBooking.php');
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
			$validator = new ConfirmBooking($_GET['confirmation-code']);
			$validator->startConfirm();
		?>
	</div>
</body>
</html>