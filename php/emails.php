<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

require_once(SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/phpqrcode/qrlib.php');

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
    global $phpmailer;

    $bookings = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
		WHERE booking_id = %s",
		$bookingId
	) );

    $registration = $wpdb->get_row( $wpdb->prepare(
		"SELECT a.*, b.send_approved_booking_email, b.send_approved_booking_email_qr_code, b.custom_fields
        FROM $seatreg_db_table_names->table_seatreg AS a
		INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
        ON a.registration_code = b.registration_code
		WHERE a.registration_code = %s",
		$registrationCode
	) );

    if( $registration->send_approved_booking_email === '0' ) {
        return true;
    }

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
    $message = '<p>' . sprintf(esc_html__("Thank you for booking at %s.", "seatreg"), esc_html($registrationName) ) . ' ' . esc_html__("Your booking is now approved", "seatreg")  . '</p>';
    $message .= '<p>' . esc_html__('Booking ID is: ', 'seatreg') . ' <strong>'. esc_html($bookingId) .'</strong>' . '</p>';
    $registrationCustomFields = json_decode($registration->custom_fields);
    $customFieldLabels = array_map(function($customField) {
        return $customField->label;
    }, json_decode($bookings[0]->custom_field_data));

    $bookingTable = '<table style="border: 1px solid black;border-collapse: collapse;">
        <tr>
            <th style=";border:1px solid black;text-align: left;padding: 6px;">' . __('Name', 'seatreg') . '</th>
            <th style=";border:1px solid black;text-align: left;padding: 6px;"">' . __('Seat', 'seatreg') . '</th>
            <th style=";border:1px solid black;text-align: left;padding: 6px;"">' . __('Room', 'seatreg') . '</th>
            <th style=";border:1px solid black;text-align: left;padding: 6px;"">' . __('Email', 'seatreg') . '</th>';
    foreach($customFieldLabels as $customFieldLabel) {
        $bookingTable .= '<th style=";border:1px solid black;text-align: left;padding: 6px;">' . esc_html($customFieldLabel) . '</th>';
    }

    $bookingTable .= '</tr>';
 

    foreach ($bookings as $booking) {
        $bookingCustomFields = json_decode($booking->custom_field_data);
        $bookingTable .= '<tr>
            <td style=";border:1px solid black;padding: 6px;"">'. esc_html($booking->first_name . ' ' .  $booking->last_name) .'</td>
            <td style=";border:1px solid black;padding: 6px;"">'. esc_html($booking->seat_nr) . '</td>
            <td style=";border:1px solid black;padding: 6px;"">'. esc_html($booking->room_name) . '</td>
            <td style=";border:1px solid black;padding: 6px;"">'. esc_html($booking->email) . '</td>';

            foreach($bookingCustomFields as $bookingCustomField) {
                $valueToDisplay = $bookingCustomField->value;
                $customFieldObject = array_filter($registrationCustomFields, function($custField) use($bookingCustomField) {
                    return $custField->label === $bookingCustomField->label;
                });

                if($customFieldObject[0] && $customFieldObject[0]->type = 'check') {
                    $valueToDisplay = $bookingCustomField->value === '1' ? esc_html__('Yes', 'seatreg') : esc_html__('No', 'seatreg');
                }
                $bookingTable .= '<td style=";border:1px solid black;padding: 6px;"">'. esc_html($valueToDisplay) . '</td>';
            }
        
        $bookingTable .= '</tr>';
    }

    $bookingTable .= '</table>';
    $message .= $bookingTable;
    $qrType = $registration->send_approved_booking_email_qr_code;
    
    if( extension_loaded('gd') && $qrType !== null ) {
        $tempDir = get_temp_dir();
        
        $bookingCheckURL = get_site_url() . '?seatreg=booking-status&registration=' . $registration->registration_code . '&id=' . $bookingId;
        $qrContent = $qrType === 'booking-id' ? $bookingId : $bookingCheckURL;

        QRcode::png($qrContent, $tempDir.$bookingId.'.png', QR_ECLEVEL_L, 4);

        add_action( 'phpmailer_init', function(&$phpmailer)use($uid,$name,$bookingId,$tempDir){
            $phpmailer->AddEmbeddedImage( $tempDir.$bookingId.'.png', 'qrcode', 'qrcode.png');
        });
        $message .= '<br><img src="cid:qrcode" />';
    }
    
    $isSent = wp_mail($bookerEmail, "Your booking at $registrationName is approved", $message, array(
        "Content-type: text/html",
        "FROM: $adminEmail"
    ));

    if($isSent) {
        seatreg_add_activity_log('booking', $bookingId, "Approved booking email sent to $bookerEmail", false);
    }
}