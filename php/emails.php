<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

require_once(SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/phpqrcode/qrlib.php');

function getEmailFromAddress($emailFromAddress) {
    return $emailFromAddress ? $emailFromAddress : get_option( 'admin_email' );
}

function seatreg_send_booking_notification_email($registrationCode, $bookingId, $emailAddress) {
    $registration = SeatregRegistrationRepository::getRegistrationWithOptionsByCode($registrationCode);
    $bookings = SeatregBookingRepository::getBookingsById($bookingId);
    $roomData = json_decode($registration->registration_layout)->roomData;
    $registrationCustomFields = json_decode($registration->custom_fields);
    $emailToSend = $registration->notification_email ?? get_option( 'admin_email' );
    $fromAddress = getEmailFromAddress($registration->email_from_address);
    $registrationName = esc_html($registration->registration_name);

    foreach ($bookings as $booking) {
        $booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($roomData, $booking->room_uuid);
    }
   
    $message = esc_html__("Hello", 'seatreg') . "<br>" . sprintf(esc_html__("This is a notification email telling you that %s has a new booking", "seatreg"), $registrationName ) . "<br><br>" . esc_html__("You can disable booking notification in options if you don't want to receive them.", "seatreg") . "<br><br>";
    $message .= SeatregBookingService::generateBookingTable($registrationCustomFields, $bookings, $registration);

    wp_mail($emailToSend, sprintf(esc_html__("%s has a new booking", "seatreg"), $registrationName), $message, array(
        "Content-type: text/html",
        "FROM: $fromAddress"
    ));
}

function seatreg_send_approved_booking_email($bookingId, $registrationCode, $template) {
    global $phpmailer;

    $GLOBALS['seatreg_qr_code_bookingid'] = $bookingId;
    $bookings = SeatregBookingRepository::getBookingsById($bookingId);
    $registration = SeatregRegistrationRepository::getRegistrationWithOptionsByCode($registrationCode);
    $registrationCustomFields = json_decode($registration->custom_fields);
    $roomData = json_decode($registration->registration_layout)->roomData;
    foreach ($bookings as $booking) {
        $booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($roomData, $booking->room_uuid);
    }
    $isSingleBooking = count($bookings) === 1;
    $registrationName = $registration->registration_name;
    $bookerEmail = $bookings[0]->booker_email;
    $bookingStatusUrl = seatreg_get_registration_status_url($registration->registration_code, $bookingId);
    $emailSubject = $registration->approved_booking_email_subject ? $registration->approved_booking_email_subject : sprintf(esc_html__("Your booking at %s is approved", "seatreg"), $registrationName);

    if(!$bookerEmail) {
        //No booker email detected. Booker email column was added with version 1.7.0.
        if($isSingleBooking) {
            $bookerEmail = $bookings[0]->email;
        }else {
            seatreg_add_activity_log('booking', $bookingId, "Not able to send out approved booking email. Booker email not specified", false);

            return false;
        }
    }

    $fromEmail = getEmailFromAddress($registration->email_from_address);
    $message = '';
    $qrType = $registration->send_approved_booking_email_qr_code;

    if($template) {
        $message = SeatregTemplateService::approvedBookingTemplateProcessing($template, $bookingStatusUrl, $bookings, $registrationCustomFields, $bookingId, $registration);
    }else {
        $message = '<p>' . sprintf(esc_html__("Thank you for booking at %s.", "seatreg"), esc_html($registrationName) ) . ' ' . esc_html__("Your booking is now approved", "seatreg")  . '</p>';
        $message .= '<p>';
        $message .= esc_html__('Booking ID: ', 'seatreg') . ' <strong>'. esc_html($bookingId) .'</strong><br>';
        $message .= esc_html__('Booking status link:', 'seatreg') . ' <a href="'. $bookingStatusUrl .'" target="_blank">'. esc_url($bookingStatusUrl) .'</a>';
        $message .= '</p>';

        $bookingTable = SeatregBookingService::generateBookingTable($registrationCustomFields, $bookings, $registration);
        $message .= $bookingTable;
        
        if( SeatregBookingService::getBookingTotalCost($bookingId, $registration->registration_layout) > 0 ) {
            $message .= '<br>';
            $message .= SeatregBookingService::generatePaymentTable($bookingId);
        }
    }

    if( extension_loaded('gd') && $qrType ) {
        $qrContent = SeatregRegQRCodeService::getQRCodeContent( $bookingId, $registration->registration_code, $qrType);
        SeatregRegQRCodeService::generateQRCodeImage($qrContent, $bookingId);
        
        add_action( 'phpmailer_init', function($phpmailer){
            $bookingId = $GLOBALS['seatreg_qr_code_bookingid'];
            $phpmailer->AddEmbeddedImage( SEATREG_TEMP_FOLDER_DIR. '/' .$bookingId.'.png', 'qrcode', 'qrcode.png');
        });
        
        $message .= '<br><img src="cid:qrcode" />';
    }
    
    $isSent = wp_mail($bookerEmail, $emailSubject, $message, array(
        "Content-type: text/html",
        "FROM: $fromEmail"
    ));

    if($isSent) {
        $activityMessage = $qrType ? "Approved booking email with QR Code sent to $bookerEmail": "Approved booking email sent to $bookerEmail";
        seatreg_add_activity_log('booking', $bookingId, $activityMessage, false);
        return true;
    }
    return false;
}

function seatreg_sent_email_verification_email($confCode, $bookerEmail, $registrationName, $template, $emailFromAddress, $customEmailSubject) {
    $confirmationURL = get_site_url() . '?seatreg=booking-confirm&confirmation-code='. $confCode;
    $fromEmail = getEmailFromAddress($emailFromAddress);
    $message = '';
    $emailSubject = $customEmailSubject ? $customEmailSubject : esc_html__('Booking email verification', 'seatreg');

    if($template) {
        $message = SeatregTemplateService::emailVerificationTemplateProcessing($template, $confirmationURL);
    }else {
        $message =  '<p>' . sprintf(esc_html__('Thank you for booking at %s', 'seatreg'), $registrationName) . '</p>' .
        '<p>' . esc_html__('Click on the link below to complete email verification', 'seatreg') . '</p>
        <a href="' .  esc_url($confirmationURL) .'" >'. esc_html($confirmationURL) .'</a><br/>
        ('. esc_html__('If you can\'t click then copy and paste it into your web browser', 'seatreg') . ')<br/><br/>';
    }

    return wp_mail($bookerEmail, $emailSubject, $message, array(
        "Content-type: text/html",
        "FROM: $fromEmail"
    ));
}

function seatreg_send_pending_booking_email($registrationName, $bookerEmail, $bookingCheckURL, $template, $emailFromAddress, $customEmailSubject) {
    $fromEmail = getEmailFromAddress($emailFromAddress);
    $message = '';
    $emailSubject = $customEmailSubject ? $customEmailSubject : esc_html__('Booking update', 'seatreg');

    if($template) {
        $message = SeatregTemplateService::pendingBookingTemplateProcessing($template, $bookingCheckURL);
    }else {
        $message =  '<p>' . esc_html__('Your booking is now in pending state. Registration admin needs to approve it', 'seatreg') . '</p>' .
        '<p>' . esc_html__('You can look your booking at the following link', 'seatreg') . '</p>' .
        '<a href="' .  esc_url($bookingCheckURL) .'" >'. esc_html($bookingCheckURL) . '</a>';
    }
    
    return wp_mail($bookerEmail, $emailSubject, $message, array(
        "Content-type: text/html",
        "FROM: $fromEmail"
    ));
}