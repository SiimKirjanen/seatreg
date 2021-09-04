<?php
/*
	Plugin Name: SeatReg
	Plugin URI: https://github.com/SiimKirjanen/seatreg
	Description: Create and manage seat registrations. Design your own seat maps and manage seat bookings
	Author: Siim Kirjanen
	Author URI: https://github.com/SiimKirjanen
	Text Domain: seatreg
	Domain Path: /languages
	Version: 1.3.0
	Requires at least: 5.3
	Requires PHP: 7.2.28
	License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
require_once( 'php/constants.php');

if( is_admin() ) {
	require( plugin_dir_path( __FILE__ ) . 'php/enqueue_admin.php' );
	require( plugin_dir_path( __FILE__ ) . 'php/seatreg_admin_panel.php' );	
}

if( !is_admin() ) {
	require( plugin_dir_path( __FILE__ ) . 'php/enqueue_public.php' );
}

require_once( plugin_dir_path( __FILE__ ) . 'php/seatreg_functions.php' );
require_once( plugin_dir_path( __FILE__ ) . 'php/SeatregJsonResponse.php' );


//Actions
require_once( plugin_dir_path( __FILE__ ) . 'php/seatreg_actions.php' );

//Hooks
function seatreg_plugin_activate() {
	seatreg_set_up_db();
}
register_activation_hook(__FILE__, "seatreg_plugin_activate");

//Filters
require_once( plugin_dir_path( __FILE__ ) . 'php/seatreg_filters.php' );

//shortcode
require_once( plugin_dir_path( __FILE__ ) . 'php/seatreg_shortcode.php' );