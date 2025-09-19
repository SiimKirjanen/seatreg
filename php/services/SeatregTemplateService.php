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

    public static function replaceBookingTable($template, $bookings, $registrationCustomFields, $registration) {
        $bookingTable = SeatregBookingService::generateBookingTable($registrationCustomFields, $bookings, $registration);

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

    public static function replacePaymentTable($template, $bookingId, $couponsEnabled, $appliedCoupon) {
        $paymentTable = SeatregBookingService::generatePaymentTable($bookingId, $couponsEnabled, $appliedCoupon);

        return str_replace(SEATREG_TEMPLATE_PAYMENT_TABLE, $paymentTable, $template);
    }

    public static function replaceCustomApprovedEmailText($template, $bookings) {
        $customText = $bookings[0]->custom_text_for_approved_email ?? '';

        return str_replace(SEATREG_TEMPLATE_BOOKING_APPROVED_EMAIL_CUSTOM_TEXT, $customText, $template);
    }

    public static function approvedBookingTemplateProcessing($template, $bookingStatusLink, $bookings, $registrationCustomFields, $bookingId, $registration, $couponsEnabled, $appliedCoupon) {
        $template = self::sanitizeTemplate($template);
        $template = self::replaceLineBreaksWithBrTags($template);
        $template = self::replaceBookingStatusLink($template, $bookingStatusLink);
        $template = self::replaceBookingTable($template, $bookings, $registrationCustomFields, $registration);
        $template = self::replaceBookingId($template, $bookingId);
        $template = self::replacePaymentTable($template, $bookingId, $couponsEnabled, $appliedCoupon);
        $template = self::replaceCustomApprovedEmailText($template, $bookings);
        
        $message = $template;

        return $message;
    }
}