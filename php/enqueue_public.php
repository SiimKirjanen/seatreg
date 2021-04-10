<?php

//remove queued styles from registration view page
add_action('wp_print_styles', 'seatreg_remove_all_styles', 100);
function seatreg_remove_all_styles() {
	if( seatreg_is_registration_view_page() ) {
		global $wp_styles;
		$allowedToLoad = array('seatreg-registration-style', 'google-open-sans');
    	$wp_styles->queue = $allowedToLoad;
	}
}

//only allow spesific scripts to load on registration view page
add_action('wp_print_scripts', 'seatreg_remove_all_scripts', 100);
function seatreg_remove_all_scripts() {
	if( seatreg_is_registration_view_page() ) {
		global $wp_scripts;
		$allowedToLoad = array('jquery', 'seatreg-registration', 'date-format', 'jquery-powertip', 'iscroll-zoom', 'modernizr');
		$wp_scripts->queue = $allowedToLoad;
	}
}

add_action( 'wp_enqueue_scripts', 'seatreg_public_scripts_and_styles' );
function seatreg_public_scripts_and_styles() {
	if ( seatreg_is_registration_view_page() ) {
		$manifestFileContents = file_get_contents(SEATREG_PLUGIN_FOLDER_URL . 'rev-manifest.json');
		$manifest = json_decode($manifestFileContents, true);
		wp_enqueue_style('google-open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:400,700', array(), '1.0.0', 'all');
		wp_enqueue_style('seatreg-registration-style', SEATREG_PLUGIN_FOLDER_URL . 'registration/css/' . $manifest['registration.min.css'] , array(), '1.0.0', 'all');
		wp_enqueue_script("jquery");
		wp_enqueue_script('modernizr', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/modernizr.custom.89593.min.js' , array(), '2.8.3', false);
		wp_enqueue_script('date-format', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/date.format.js' , array(), '1.0.0', true);
		wp_enqueue_script('iscroll-zoom', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/iscroll-zoom.js' , array(), '5.1.3', true);
		wp_enqueue_script('jquery-powertip', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/jquery.powertip.js' , array(), '1.2.0', true);
		wp_enqueue_script('seatreg-registration', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/registration.js' , array('jquery', 'date-format', 'iscroll-zoom', 'jquery-powertip'), '1.0.0', true);
	}
}