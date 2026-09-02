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

    /**
     *
     * Can a date be booked? An empty list of calendar dates leaves every date open,
     * the way booking validation reads it.
     * @param string|null $calendarDates Comma separated dates of the registration
     * @param string $date Date in CALENDAR_DATE_FORMAT
     *
     * @return bool
     *
    */
    public static function isDateAvailable($calendarDates, $date) {
        if( !$calendarDates ) {
            return true;
        }

        return in_array( $date, explode(',', $calendarDates) );
    }
}