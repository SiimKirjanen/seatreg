<?php

	//=================================
	/* Booking confirm */
	//=================================

	if( empty($_GET['confirmation-code']) ) {
		exit();
	}
	
	require_once('./../reg/php/reg_functions.php');
	require_once('./NewBooking.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="shortcut icon" type="image/png" href="favicon.png"/>
	<title>SeatReg.com</title>
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