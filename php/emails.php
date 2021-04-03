<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

function seatreg_send_booking_notification_email($registrationName, $bookedSeatsString, $emailAddress) {
    $message = "Hello <br>This is a notification email telling you that " . esc_html($registrationName) . " has a new booking <br><br> $bookedSeatsString <br><br> You can disable booking notification in options if you don't want to receive them.";
    $adminEmail = get_option( 'admin_email' );

    wp_mail($adminEmail, "$registrationName has a new booking", $message, array(
        "Content-type: text/html",
        "FROM: $adminEmail"
    ));
}