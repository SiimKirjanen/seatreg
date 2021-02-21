<?php

	//=================================
	/* Booking confirm */
	//=================================

	if( empty($_GET['confirmation-code']) ) {
		exit();
	}
	
	require_once('./../registration/php/reg_functions.php');
	require_once('./NewBooking.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="icon" href="<?php echo get_site_icon_url(); ?>" />
	<title>Booking confirm</title>
</head>
<body>
	<div>
		<?php
			$validator = new NewBooking($_GET['confirmation-code']);
			$validator->startConfirm();
		?>
	</div>
</body>
</html>