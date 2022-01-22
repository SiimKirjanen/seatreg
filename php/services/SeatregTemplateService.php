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

        return str_replace('[verification-link]', $confirmationURL, $template);
    }
    public static function replaceBookingStatusLink($template, $bookingStatusLink) {
        $bookingStatusLink = self::constructLink($bookingStatusLink);

        return str_replace('[status-link]', $bookingStatusLink, $template);
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
        $message= self::replaceBookingStatusLink($template, $bookingStatusLink);
        
        return $message;
    }
}