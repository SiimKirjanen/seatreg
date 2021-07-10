<?php
require_once(SEATREG_PLUGIN_FOLDER_DIR . 'registration/php/reg_functions.php');
require_once(SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_strings.php');

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
	if ( seatreg_is_registration_view_page() && !empty($_GET['c']) ) {
		$manifestFileContents = file_get_contents(SEATREG_PLUGIN_FOLDER_DIR . 'rev-manifest.json');
		$manifest = json_decode($manifestFileContents, true);

		wp_enqueue_style('google-open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:400,700', array(), '1.0.0', 'all');
		wp_enqueue_style('seatreg-registration-style', SEATREG_PLUGIN_FOLDER_URL . 'registration/css/' . $manifest['registration.min.css'] , array(), '1.0.0', 'all');
		wp_enqueue_script("jquery");
		wp_enqueue_script('modernizr', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/modernizr.custom.89593.min.js' , array(), '2.8.3', false);
		wp_enqueue_script('date-format', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/date.format.js' , array(), '1.0.0', true);
		wp_enqueue_script('iscroll-zoom', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/iscroll-zoom.js' , array(), '5.1.3', true);
		wp_enqueue_script('jquery-powertip', SEATREG_PLUGIN_FOLDER_URL . 'js/jquery.powertip.js' , array(), '1.2.0', true);
		wp_enqueue_script('seatreg-registration', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/registration.js' , array('jquery', 'date-format', 'iscroll-zoom', 'jquery-powertip'), '1.0.1', true);

		$data = seatreg_get_options_reg($_GET['c']);
		$seatsInfo = json_encode( seatreg_stats_for_registration_reg($data->registration_layout, $data->registration_code) );
		$registrationTime = seatreg_registration_time_status( $data->registration_start_timestamp,  $data->registration_end_timestamp );
		if($data->show_bookings == '1') {
			$registrations = json_encode(seatreg_get_registration_bookings_reg($_GET['c'], true)); //also names
		}else {
			$registrations = json_encode(seatreg_get_registration_bookings_reg($_GET['c'], false));  //no names
		}

		$inlineScript = 'function showErrorView(title) {';
			$inlineScript .= "jQuery('body').addClass('error-view').html(";
				$inlineScript .= '<div>An error occured</div><img src="' . SEATREG_PLUGIN_FOLDER_URL . 'img/monkey.png" alt="monkey" /><div>title</div>';
			$inlineScript .= ');';
		$inlineScript .= '}';
		
		$inlineScript = 'try {';
			$inlineScript .= 'var seatregPluginFolder = "' . SEATREG_PLUGIN_FOLDER_URL . '";';
			$inlineScript .= "var seatregTranslations = jQuery.parseJSON('" .  wp_json_encode( seatreg_generate_registration_strings() ) . "');";
			$inlineScript .= 'var seatLimit = ' . esc_js($data->seats_at_once) . ';';
			$inlineScript .= 'var gmail = ' . esc_js($data->gmail_required) . ';';
			$inlineScript .= 'var dataReg = jQuery.parseJSON(' . wp_json_encode($data->registration_layout) . ');';
			$inlineScript .= 'var roomsInfo = jQuery.parseJSON(' . wp_json_encode($seatsInfo) . ');';
			$inlineScript .= 'var custF = jQuery.parseJSON(' . wp_json_encode($data->custom_fields) . ');';
			$inlineScript .= 'var regTime = "' . esc_js($registrationTime) . '";';
			$inlineScript .= 'var registrations = jQuery.parseJSON(' . wp_json_encode($registrations) . ');';
			$inlineScript .= 'var ajaxUrl = "'. admin_url('admin-ajax.php') . '";';
			$inlineScript .= '} catch(err) {';
				$inlineScript .= "showErrorView('Data initialization failed');";
				$inlineScript .= "console.log(err);";
			$inlineScript .= 
		$inlineScript .= '}';

		wp_add_inline_script('seatreg-registration', $inlineScript, 'before');
		wp_localize_script('seatreg-registration', 'WP_Seatreg', array(
			'SEATREG_CUSTOM_TEXT_FIELD_MAX_LENGTH' => SEATREG_CUSTOM_TEXT_FIELD_MAX_LENGTH,
		));
	}
}