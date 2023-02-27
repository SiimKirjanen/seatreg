<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregCalendarService {

    /**
     *
     * Get booking filtering date. If calendar mode not enabled return null
     * @param object $registrationData Registration options
     * 
     * @return string|null
     * 
    */
    public static function getBookingFilteringDate($registrationOptions) {
        return $registrationOptions->using_calendar === "1" ? date('d.m.Y') : null;
    }
}