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

	$confirmationCode = sanitize_text_field($_GET['confirmation-code']);
	$options = SeatregOptionsRepository::getOptionsByConfirmationCode($confirmationCode);
	$validator = new SeatregConfirmBooking($confirmationCode);
	ob_start();
	$validator->startConfirm();
	$confirmResult = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="<?php echo esc_url(get_site_icon_url()); ?>" />
	<title>
		<?php esc_html_e('Booking confirm', 'seatreg'); ?>
	</title>
	<?php SeatregPublicPageService::renderStyles($options); ?>
	<?php wp_head(); ?>
</head>
<body>
	<?php
		SeatregPublicPageService::renderPageStart(array(
			'title' => $validator->status === 'success' ? __('Booking confirmed', 'seatreg') : __('We could not confirm your booking', 'seatreg'),
			'name' => wp_unslash($validator->getRegistrationName()),
			'logoId' => $options ? $options->page_logo : null,
		));
	?>
		<div class="page-wrap">
			<?php echo wp_kses_post($confirmResult); ?>
		</div>
	<?php
		SeatregPublicPageService::renderPageEnd();
	?>
</body>
</html>
