<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

require_once('seatreg_strings.php');

/*
	============================================
		SeatReg Admin Page enqueue functions
	============================================
*/

add_action('admin_enqueue_scripts', 'seatreg_load_admin_scripts');
function seatreg_load_admin_scripts($hook) {
	$screen = get_current_screen();
	$allowedToLoadStylesAndScripts = array(
		'toplevel_page_seatreg-welcome',
		'seatreg_page_seatreg-overview',
		'seatreg_page_seatreg-builder',
		'seatreg_page_seatreg-options',
		'seatreg_page_seatreg-management',
		'seatreg_page_seatreg-tools',
	);

	if(in_array($screen->id, $allowedToLoadStylesAndScripts)) {
		wp_enqueue_style('jquery-ui-style', plugins_url('css/custom-theme/jquery-ui-1.9.2.custom.min.css', dirname(__FILE__) ), array(), '1.9.2', 'all');
		wp_enqueue_style('bootstrap-styles', plugins_url('css/bootstrap.min.css', dirname(__FILE__) ), array(), '3.1.1', 'all');
		wp_enqueue_style('alertify-core', plugins_url('css/alertify.core.css', dirname(__FILE__) ), array(), '1.0.0', 'all');
		wp_enqueue_style('alertify-default', plugins_url('css/alertify.default.css', dirname(__FILE__) ), array(), '1.0.0', 'all');
		wp_enqueue_style('vanilla_picker_style', plugins_url('js/vanilla-picker/dist/vanilla-picker.csp.css', dirname(__FILE__) ), array(), '2.12.1', 'all');
		wp_enqueue_style('seatreg_builder_style', plugins_url('css/seatreg_builder.min.css', dirname(__FILE__) ), array(), '1.1.0', 'all');
		wp_enqueue_style('seatreg_admin_styles', plugins_url('css/seatreg_admin.min.css', dirname(__FILE__) ), array(), '1.6.0', 'all');
		wp_enqueue_style('Google_open_sans', 'https://fonts.googleapis.com/css?family=Open+Sans:400,600', array(), '1.0.0', 'all');
		wp_enqueue_style('powertip_style', plugins_url('css/jquery.powertip.css', dirname(__FILE__) ), array(), '1.0.0', 'all');

		wp_enqueue_script("jquery");
		wp_enqueue_script("jquery-form");
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('jquery-ui-draggable');
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_script('jquery-ui-selectable');
		wp_enqueue_script('jquery-effects-core');
		wp_enqueue_script('jquery-effects-bounce');

		wp_enqueue_script('selectable_scroll', plugins_url('js/selectableScroll.js', dirname(__FILE__) ), array('jquery','jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-selectable'), '1.0.0', true);
		wp_enqueue_script('bootstrap-3-1-1', plugins_url('js/bootstrap.min.js', dirname(__FILE__) ), array('jquery'), '3.1.1', true);
		wp_enqueue_script('alertify', plugins_url('js/alertify.js', dirname(__FILE__) ), array('jquery'), '1.0.0', true);
		wp_enqueue_script('easytabs', plugins_url('js/jquery.easytabs.js', dirname(__FILE__) ), array('jquery'), '1.0.0', true);
		wp_enqueue_script('vanilla_picker', plugins_url('js/vanilla-picker/dist/vanilla-picker.js', dirname(__FILE__) ), array('jquery'), '2.12.1', true);
		wp_enqueue_script('powertip', plugins_url('js/jquery.powertip.js', dirname(__FILE__) ), array('jquery'), '1.2.0', true);

		wp_enqueue_script('date-format', plugins_url('js/date.format.js', dirname(__FILE__) ), array('jquery'), '1.0.0', true);
		wp_enqueue_script('seatreg_admin_chart', plugins_url('js/Chart.min.js', dirname(__FILE__) ), array('jquery'), '1.0.0', true);
		wp_enqueue_script('seatreg_admin', plugins_url('js/seatreg_admin.js', dirname(__FILE__) ), array('jquery', 'powertip', 'seatreg_admin_chart'), '1.1.0', true);
		wp_enqueue_script('jstz', plugins_url('js/jstz-1.0.4.min.js', dirname(__FILE__) ), array(), '1.0.4', true);
		wp_enqueue_script('seatreg_builder_script', plugins_url('js/build.js', dirname(__FILE__) ), array('jquery','jquery-ui-core','alertify','vanilla_picker','powertip'), '1.4.0', true);

		wp_localize_script('seatreg_admin', 'WP_Seatreg', array(
			'nonce' => wp_create_nonce('seatreg-admin-nonce'),
			'plugin_dir_url' => plugin_dir_url( dirname( __FILE__ ) ),
			'translations' => seatreg_generate_admin_strings(), 
		));
	}
}
