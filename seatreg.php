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

function seatregPluginActivate() {
	seatreg_set_up_db();
}

//actions
if( is_admin() ) {
	add_action('admin_menu', 'seatreg_add_plugin_menu');
}

//hooks
register_activation_hook(__FILE__, "seatregPluginActivate");

//filters
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

add_filter('admin_footer_text', 'remove_admin_footer_text');
function remove_admin_footer_text() {
    echo '';
}