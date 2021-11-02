<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

function seatreg_send_booking_notification_email($registrationName, $bookedSeatsString, $emailAddress) {
    $message = esc_html__("Hello", 'seatreg') . "<br>" . sprintf(esc_html__("This is a notification email telling you that %s has a new booking", "seatreg"), esc_html($registrationName) ) . "<br><br> $bookedSeatsString <br><br>" . esc_html__("You can disable booking notification in options if you don't want to receive them.", "seatreg");
    $adminEmail = get_option( 'admin_email' );

    wp_mail($adminEmail, "$registrationName has a new booking", $message, array(
        "Content-type: text/html",
        "FROM: $adminEmail"
    ));
}

function seatreg_send_approved_booking_email($bookingId, $registrationCode) {
    global $seatreg_db_table_names;
	global $wpdb;

    $bookings = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
		WHERE booking_id = %s",
		$bookingId
	) );
    $registration = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $seatreg_db_table_names->table_seatreg
		WHERE registration_code = %s",
		$registrationCode
	) );
    $registrationName = $registration->registration_name;
    $bookerEmail = $bookings[0]->booker_email;
    $adminEmail = get_option( 'admin_email' );
    $message = "<h2>" . sprintf(esc_html__("Thank you for booking at %s", "seatreg"), esc_html($registrationName) ) . "</h2>";

    $isSent = wp_mail($bookerEmail, "Your booking at $registrationName is approved", $message, array(
        "Content-type: text/html",
        "FROM: $adminEmail"
    ));

    if($isSent) {
        seatreg_add_activity_log('booking', $bookingId, "Approved booking email sent to $bookerEmail", false);
    }
}