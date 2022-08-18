<?php
/*
	Plugin Name: SeatReg
	Plugin URI: https://github.com/SiimKirjanen/seatreg
	Description: Create and manage seat registrations. Design your own seat maps and manage seat bookings
	Author: Siim Kirjanen
	Author URI: https://github.com/SiimKirjanen
	Text Domain: seatreg
	Version: 1.25.0
	Requires at least: 5.3
	Requires PHP: 7.2.28
	License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

require( 'php/constants.php' );
require( 'php/repositories/SeatregBookingRepository.php' );
require( 'php/repositories/SeatregRegistrationRepository.php' );
require( 'php/repositories/SeatregPaymentRepository.php' );
require( 'php/repositories/SeatregOptionsRepository.php' );
require( 'php/repositories/SeatregActivityLogRepository.php' );
require( 'php/repositories/SeatregPaymentLogRepository.php' );
require( 'php/services/SeatregRegistrationService.php' );
require( 'php/services/SeatregBookingService.php' );
require( 'php/services/SeatregPaymentService.php' );
require( 'php/services/SeatregPaymentLogService.php' );
require( 'php/services/SeatregJobService.php' );
require( 'php/services/SeatregTemplateService.php' );
require( 'php/services/SeatregLayoutService.php' );
require( 'php/services/StripeWebhooksService.php' );
require( 'php/services/SeatregOptionsService.php' );
require( 'php/emails.php' );
require( 'php/SeatregBooking.php' );
require( 'php/SeatregSubmitBookings.php' );
require( 'php/SeatregDataValidation.php' );
require( 'php/util/registration_time_status.php' );

if( is_admin() ) {
	require( 'php/enqueue_admin.php' );
	require( 'php/seatreg_admin_panel.php' );	
}

if( !is_admin() ) {
	require( 'php/enqueue_public.php' );
}

require( 'php/seatreg_functions.php' );
require( 'php/SeatregJsonResponse.php' );

//Actions
require( 'php/seatreg_actions.php' );

//Hooks
register_activation_hook( __FILE__, 'seatreg_plugin_activate' );
function seatreg_plugin_activate() {
	seatreg_set_up_db();
}

register_deactivation_hook( __FILE__, 'seatreg_plugin_deactivate' );
function seatreg_plugin_deactivate() {
	$timestamp = wp_next_scheduled( 'seatreg_pending_booking_expiration' );

	if($timestamp) {
		wp_unschedule_event( $timestamp, 'seatreg_pending_booking_expiration' );
	}
}	

//Filters
require( 'php/seatreg_filters.php' );

//shortcode
require( 'php/seatreg_shortcode.php' );

//CRON
if ( ! wp_next_scheduled( 'seatreg_pending_booking_expiration' ) ) {
    wp_schedule_event( time(), 'seatreg_expiration_schedult', 'seatreg_pending_booking_expiration' );
}