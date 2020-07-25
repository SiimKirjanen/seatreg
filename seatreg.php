<?php
/*
	Plugin Name: SeatReg
	Plugin URI: http://www.seatreg.com
	Description: Create and manage seat registrations. Design your own seat maps and manage seat bookings
	Author: Siim Kirjanen
	Text Domain: seatreg
	Version: 1.0
	
*/

	
if ( ! defined( 'ABSPATH' ) ) exit;


if( is_admin() ) {

	require( plugin_dir_path( __FILE__ ) . 'php/enqueue_admin.php' );
	require( plugin_dir_path( __FILE__ ) . 'php/seatreg_admin_panel.php' );	
}
require_once( plugin_dir_path( __FILE__ ) . 'php/seatreg_functions.php' );
require_once( plugin_dir_path( __FILE__ ) . 'php/seatreg_shortcode.php' );
require_once( plugin_dir_path( __FILE__ ) . 'php/JsonResponse.php' );


function seatregPluginActivate() {

	seatreg_set_up_db();
	
}


function seatregPluginDeactivate() {

}






//actions
if( is_admin() ) {
	add_action('admin_menu', 'addSeatregPluginMenu');  // from php/admin/seatreg_admin_panel.php
}

add_action( 'init', 'seatreg_register_shortcode' );  //from php/registration/seatreg_shortcode.php



//hooks

register_activation_hook(__FILE__, "seatregPluginActivate");
register_deactivation_hook(__FILE__, "seatregPluginDeactivate"); 


//filters

add_filter( 'admin_body_class', 'seatreg_admin_body_class' );

function seatreg_admin_body_class($classes) {

	if( !isset($_GET['page']) ) {

		return $classes;

	}

	if( $_GET['page'] == 'seatreg-welcome' || $_GET['page'] == 'seatreg-builder' ) {

		return "$classes seatreg-map-builder-page"; 

	}else {
		return $classes;
	}


}