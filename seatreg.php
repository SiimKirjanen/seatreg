<?php
/*
	Plugin Name: SeatReg
	Plugin URI: https://github.com/SiimKirjanen/seatreg
	Description: Create and manage online registrations. Design your own registration layout and manage bookings.
	Author: Siim Kirjanen
	Author URI: https://github.com/SiimKirjanen
	Text Domain: seatreg
	Version: 1.60.1
	Requires at least: 5.3
	Requires PHP: 7.2.28
	License: GPLv2 or later
*/

/*
SeatReg is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

SeatReg is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
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
require( 'php/repositories/SeatregApiTokenRepository.php' );
require( 'php/repositories/SeatregUploadsRepository.php' );
require( 'php/repositories/SeatregTimeRepository.php' );
require( 'php/repositories/SeatregCouponRepository.php' );
require( 'php/services/SeatregRegistrationService.php' );
require( 'php/services/SeatregBookingService.php' );
require( 'php/services/SeatregPaymentService.php' );
require( 'php/services/SeatregPaymentLogService.php' );
require( 'php/services/SeatregJobService.php' );
require( 'php/services/SeatregTemplateService.php' );
require( 'php/services/SeatregLayoutService.php' );
require( 'php/services/StripeWebhooksService.php' );
require( 'php/services/SeatregOptionsService.php' );
require( 'php/services/SeatregCustomFieldService.php' );
require( 'php/services/SeatregCalendarService.php' );
require( 'php/services/SeatregActionsService.php' );
require( 'php/services/SeatregPublicApiService.php' );
require( 'php/services/SeatregImageUploadService.php' );
require( 'php/services/SeatregImageDeleteService.php' );
require( 'php/services/SeatregImageCopyService.php' );
require( 'php/services/SeatregRandomGenerator.php' );
require( 'php/services/SeatregTimeService.php' );
require( 'php/services/SeatregQRCodeService.php' );
require( 'php/services/SeatregAuthService.php' );
require( 'php/services/SeatregLinksService.php' );
require( 'php/services/SeatregCSVService.php' );
require( 'php/services/SeatregImportService.php' );
require( 'php/services/SeatregSanitizationService.php' );
require( 'php/services/SeatregCouponService.php' );
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
	seatreg_capabilities_add();
}

register_deactivation_hook( __FILE__, 'seatreg_plugin_deactivate' );
function seatreg_plugin_deactivate() {
	$timestamp = wp_next_scheduled( 'seatreg_pending_booking_expiration' );

	if($timestamp) {
		wp_unschedule_event( $timestamp, 'seatreg_pending_booking_expiration' );
	}
	seatreg_capabilities_remove();
}	

//Filters
require( 'php/seatreg_filters.php' );

//shortcode
require( 'php/seatreg_shortcode.php' );

//CRON
if ( ! wp_next_scheduled( 'seatreg_pending_booking_expiration' ) ) {
    wp_schedule_event( time(), 'seatreg_expiration_schedult', 'seatreg_pending_booking_expiration' );
}

//public API
require( 'php/public_api.php' );

//Capabilities
$seatreg_trigger_side_effect = get_option('seatreg_trigger_side_effect');
if ($seatreg_trigger_side_effect !== SEATREG_TRIGGER_SIDE_EFFECT) {
	seatreg_capabilities_add();
	update_option( 'seatreg_trigger_side_effect', SEATREG_TRIGGER_SIDE_EFFECT );
}

//Uploads
if (!file_exists(SEATREG_TEMP_FOLDER_DIR)) {
	mkdir(SEATREG_TEMP_FOLDER_DIR, 0775, true);
}