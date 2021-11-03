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
    $roomData = json_decode($registration->registration_layout)->roomData;
    foreach ($bookings as $booking) {
        $booking->room_name = seatreg_get_room_name_from_layout($roomData, $booking->room_uuid);
    }
    $isSingleBooking = count($bookings) === 1;
    $registrationName = $registration->registration_name;
    $bookerEmail = $bookings[0]->booker_email;
    $bookingId = $bookings[0]->booking_id;

    if(!$bookerEmail) {
        //No booker email detected. Booker email column was added with version 1.7.0.
        if($isSingleBooking) {
            $bookerEmail = $bookings[0]->email;
        }else {
            seatreg_add_activity_log('booking', $bookingId, "Not able to send out approved booking email. Booker email not specified", false);

            return true;
        }
    }

    $adminEmail = get_option( 'admin_email' );
    $message = "<p>" . sprintf(esc_html__("Thank you for booking at %s.", "seatreg"), esc_html($registrationName) ) . ' ' . esc_html__("Your booking is now approved", "seatreg")  . "</p>";
    $message .= "<p>" . esc_html__('Booking ID is: ', 'seatreg') . ' <strong>'. $bookingId .'</strong>' . "</p>";
    $bookingTable = '<table style="border: 1px solid black;border-collapse: collapse;">
        <tr>
            <th style=";border:1px solid black;text-align: left;padding: 6px;">' . __('Name', 'seatreg') . '</th>
            <th style=";border:1px solid black;text-align: left;padding: 6px;"">' . __('Seat', 'seatreg') . '</th>
            <th style=";border:1px solid black;text-align: left;padding: 6px;"">' . __('Room', 'seatreg') . '</th>
        </tr>';

    foreach ($bookings as $booking) {
        $bookingTable .= '<tr>
            <td style=";border:1px solid black;padding: 6px;"">'. $booking->first_name . ' ' .  $booking->last_name .'</td>
            <td style=";border:1px solid black;padding: 6px;"">'. $booking->seat_nr . '</td>
            <td style=";border:1px solid black;padding: 6px;"">'. $booking->room_name . '</td>
        </tr>';
    }

    $bookingTable .= '</table>';
    $message .= $bookingTable;

    $isSent = wp_mail($bookerEmail, "Your booking at $registrationName is approved", $message, array(
        "Content-type: text/html",
        "FROM: $adminEmail"
    ));

    if($isSent) {
        seatreg_add_activity_log('booking', $bookingId, "Approved booking email sent to $bookerEmail", false);
    }
}