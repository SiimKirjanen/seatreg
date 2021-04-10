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
		seatreg_echo_booking(sanitize_text_field($_GET['registration']), sanitize_text_field($_GET['id']));
	?>
</body>
</html>
