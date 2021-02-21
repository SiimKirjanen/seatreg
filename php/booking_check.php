<?php
	//===========
	/*client may want to check if her booking exists*/
	//===========

	require_once('./util/load_wp.php');
	require_once('./seatreg_functions.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="icon" href="<?php echo get_site_icon_url(); ?>" />
	<title>Booking check</title>
</head>
<body>
	<?php
		if(!empty($_GET['registration']) && !empty($_GET['id'])) {
			seatreg_echo_booking($_GET['registration'], $_GET['id']);
		} 
	?>
</body>
</html>
