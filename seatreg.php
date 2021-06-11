<?php
/*
	Plugin Name: SeatReg
	Plugin URI: https://github.com/SiimKirjanen/seatreg
	Description: Create and manage seat registrations. Design your own seat maps and manage seat bookings
	Author: Siim Kirjanen
	Text Domain: seatreg
	Version: 1.0.4
	Requires at least: 5.4.4
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