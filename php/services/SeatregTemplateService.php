<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregTemplateService {
    public static function sanitizeTemplate($template) {
        return sanitize_textarea_field($template);
    }

    public static function replaceLineBreaksWithBrTags($template) {
        return nl2br($template);
    }

    public static function constructLink($link) {
        return '<a href="'. $link .'">'. $link .'</a>';
    }

    public static function replaceEmailVerificationLink($template, $confirmationURL) {
        $confirmationURL = self::constructLink($confirmationURL);

        return str_replace(SEATREG_TEMPLATE_EMAIL_VERIFICATION_LINK, $confirmationURL, $template);
    }

    public static function replaceBookingStatusLink($template, $bookingStatusLink) {
        $bookingStatusLink = self::constructLink($bookingStatusLink);

        return str_replace(SEATREG_TEMPLATE_STATUS_LINK, $bookingStatusLink, $template);
    }

    public static function replaceBookingTable($template, $bookings, $registrationCustomFields) {
        $bookingTable = SeatregBookingService::generateBookingTable($registrationCustomFields, $bookings);

        return str_replace(SEATREG_TEMPLATE_BOOKING_TABLE, $bookingTable, $template);
    }

    public static function replaceBookingId($template, $bookingId) {
        return str_replace(SEATREG_TEMPLATE_BOOKING_ID, '<strong>' . $bookingId . '</strong>', $template);
    }

    public static function emailVerificationTemplateProcessing($template, $confirmationURL) {
        $template = self::sanitizeTemplate($template);
        $template = self::replaceLineBreaksWithBrTags($template);
        $message = self::replaceEmailVerificationLink($template, $confirmationURL);

        return $message;
    }

    public static function pendingBookingTemplateProcessing($template, $bookingStatusLink) {
        $template = self::sanitizeTemplate($template);
        $template = self::replaceLineBreaksWithBrTags($template);
        $message = self::replaceBookingStatusLink($template, $bookingStatusLink);
        
        return $message;
    }

    public static function replacePaymentTable($template, $bookingId) {
        $paymentTable = SeatregBookingService::generatePaymentTable($bookingId);

        return str_replace(SEATREG_TEMPLATE_PAYMENT_TABLE, $paymentTable, $template);
    }

    public static function approvedBookingTemplateProcessing($template, $bookingStatusLink, $bookings, $registrationCustomFields, $bookingId) {
        $template = self::sanitizeTemplate($template);
        $template = self::replaceLineBreaksWithBrTags($template);
        $template = self::replaceBookingStatusLink($template, $bookingStatusLink);
        $template = self::replaceBookingTable($template, $bookings, $registrationCustomFields);
        $template = self::replaceBookingId($template, $bookingId);
        $template = self::replacePaymentTable($template, $bookingId);

        $message = $template;

        return $message;
    }
}