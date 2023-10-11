<?php
//===========
	/* Page that generates and displays booking PDF */
//===========

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/bookings/SeatregBookingPDF.php' );

function shouldAllowPdfGeneration($bookings, $bookingData) {
	if(!$bookings) {
		return false;
	}

	if( $bookings[0]->status === '1' &&  $bookingData->show_pending_booking_pdf === '0' ) {
		return false;
	}else if( $bookings[0]->status === '2' && $bookingData->show_approved_booking_pdf === '0' ) {
		return false;
	}

	return true;
}

if ( !defined( 'ABSPATH' ) ) {
	exit(); 
}

if( empty( $_GET['id'] )  ) {
	exit('Missing data'); 
}

$bookings = SeatregBookingRepository::getBookingsById( $_GET['id'] );
$bookingData = SeatregBookingRepository::getDataRelatedToBooking( $_GET['id'] );

if( !$bookings || !shouldAllowPdfGeneration($bookings, $bookingData) ) {
	esc_html_e('Booking not found or feature disabled', 'seatreg');

} else {
	$pdf = new SeatregBookingPDF( $_GET['id'], $bookings, $bookingData );
	$pdf->printPDF();
}