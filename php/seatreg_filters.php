<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

add_filter( 'show_admin_bar', 'seatreg_hide_admin_bar_from_registration_view' );
function seatreg_hide_admin_bar_from_registration_view(){
	if( is_user_logged_in() ) {
		if( seatreg_is_registration_view_page() ) {
			return false;
		}
		return true;
	}

	return false;
}

add_filter( 'page_template', 'seatreg_page_template' );
function seatreg_page_template( $page_template ){
    if ( seatreg_is_registration_view_page() ) {
        $page_template = SEATREG_PLUGIN_FOLDER_DIR . '/registration/index.php';
    }

    return $page_template;
}

add_filter('init', 'seatreg_virtual_pages');
function seatreg_virtual_pages() {

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'pdf' ) {
		seatreg_is_user_logged_in_and_has_permissions();
		seatreg_validate_bookings_file_input();		
		require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/bookings/SeatregBookingsPDF.php' );

		$pdf = new SeatregBookingsPDF( isset($_GET['s1']), isset($_GET['s2']), $_GET['zone'], $_GET['code'] );
		$pdf->printPDF();
	
		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'xlsx' ) {
		seatreg_is_user_logged_in_and_has_permissions();
		seatreg_validate_bookings_file_input();	
		require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/bookings/SeatregBookingsXlsx.php' );

		$xlsx = new SeatregBookingsXlsx( isset($_GET['s1']), isset($_GET['s2']), $_GET['zone'], $_GET['code'] );
		$xlsx->printXlsx();

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'text' ) {
		seatreg_is_user_logged_in_and_has_permissions();
		seatreg_validate_bookings_file_input();	
		require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/bookings/SeatregBookingsTxt.php' );

		$txt = new SeatregBookingsTxt( isset($_GET['s1']), isset($_GET['s2']), $_GET['zone'], $_GET['code'] );
		$txt->printTxt();

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'booking-confirm' ) {
		include SEATREG_PLUGIN_FOLDER_DIR  . 'php/booking_confirm.php';

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'booking-status' ) {
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