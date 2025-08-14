<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregCalendarService {

    /**
     *
     * Get booking filtering date. If calendar mode not enabled return null
     * @param string $usingCalendar Does registration use calendar mode?
     * 
     * @return string|null
     * 
    */
    public static function getBookingFilteringDate($usingCalendar) {
        if ($usingCalendar === "1") {
            $dt = current_datetime();

            return $dt->format(CALENDAR_DATE_FORMAT);
        }

        return null;
    }

    public static function getBookingFilteringDateForRegistrationView($usingCalendar, $calendarDate) {
        if($usingCalendar && $calendarDate !== null) {
            return $calendarDate;
        }
        return self::getBookingFilteringDate($usingCalendar);
    }
}