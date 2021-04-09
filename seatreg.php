<?php
/*
	Plugin Name: SeatReg
	Plugin URI: https://github.com/SiimKirjanen/seatreg_wordpress
	Description: Create and manage seat registrations. Design your own seat maps and manage seat bookings
	Author: Siim Kirjanen
	Text Domain: seatreg
	Version: 1.0.0
	Requires at least: 5.4.4
	Requires PHP: 7.2.28
	License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

if( is_admin() ) {
	require( plugin_dir_path( __FILE__ ) . 'php/enqueue_admin.php' );
	require( plugin_dir_path( __FILE__ ) . 'php/seatreg_admin_panel.php' );	
}

require_once( plugin_dir_path( __FILE__ ) . 'php/seatreg_functions.php' );
require_once( plugin_dir_path( __FILE__ ) . 'php/JsonResponse.php' );

function seatreg_plugin_activate() {
	seatreg_set_up_db();
}

//actions
if( is_admin() ) {
	add_action('admin_menu', 'seatreg_add_plugin_menu');
}

//hooks
register_activation_hook(__FILE__, "seatreg_plugin_activate");

//filters
add_filter( 'page_template', 'seatreg_page_template' );
function seatreg_page_template( $page_template ){
    if ( isset($_GET['seatreg']) &&  $_GET['seatreg'] == 'registration' ) {
        $page_template = plugin_dir_path( __FILE__ ) . '/registration/index.php';
    }

    return $page_template;
}

add_action( 'wp_enqueue_scripts', 'seatreg_public_scripts_and_styles' );
function seatreg_public_scripts_and_styles() {
    wp_enqueue_script("jquery");
}

add_filter('init', 'seatreg_virtual_pages');
function seatreg_virtual_pages() {

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'pdf') {
		include SEATREG_PLUGIN_FOLDER_DIR . 'bookings/pdf.php';

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'xlsx') {
		include SEATREG_PLUGIN_FOLDER_DIR . 'bookings/xlsx.php';

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'text') {
		include SEATREG_PLUGIN_FOLDER_DIR  . 'bookings/text.php';

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'booking-confirm') {
		include SEATREG_PLUGIN_FOLDER_DIR  . 'php/booking_confirm.php';

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'booking-status') {
		include SEATREG_PLUGIN_FOLDER_DIR  . 'php/booking_check.php';

		die();
	}
}

add_filter( 'admin_body_class', 'seatreg_admin_body_class' );
function seatreg_admin_body_class($classes) {
	if( !isset($_GET['page']) ) {
		return $classes;
	}

	if( $_GET['page'] == 'seatreg-welcome' || $_GET['page'] == 'seatreg-builder' ) {
		return "$classes seatreg-map-builder-page"; 
	}

	return $classes;
}

add_filter('admin_footer_text', 'seatreg_remove_admin_footer_text');
function seatreg_remove_admin_footer_text() {
    echo '';
}