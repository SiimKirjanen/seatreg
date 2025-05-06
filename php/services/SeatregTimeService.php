<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregTimeService {
    /**
     *
     * Get WordPress 24h time string
     * @return string Return current time (24h format)
     *
    */
    public static function getCurrent24TimeString() {
        return current_time('H:i');
    }

    /**
     *
     * Get current WordPress DateTime
     * @return DateTimeImmutable
     *
    */
    public static function getCurrentDateTime() {
        return current_datetime();
    }

    /**
     *
     * Get minutes out of 24h time string
     * @param string $time String representation of 24h time ('H:i')
     * @return int Return number of minutes
     *
    */
    public static function getMinutesOutOf24TimeString($time) {
        return (intval(substr($time, 0, 2)) * 60) + intval(substr($time, 3, 2));
    }

    /**
     *
     * Registration start time check
     * @param string|null $registrationStartTime representation of 24h time ('H:i')
     * @return bool Return true when start time check passes. Otherwise false.
     *
    */
    public static function registrationStartTimeCheck($registrationStartTime) {

        if( $registrationStartTime === null ) {
			return true;
		}

        $currentTime = self::getCurrent24TimeString();
		$time1Minutes = self::getMinutesOutOf24TimeString($currentTime);
		$time2Minutes = self::getMinutesOutOf24TimeString($registrationStartTime);

		if( $time1Minutes < $time2Minutes ) {
            return false;
		}

        return true;
    }

    /**
     *
     * Registration end time check
     * @param string|null $registrationEndTime representation of 24h time ('H:i')
     * @return bool Return true when end time check passes. Otherwise false.
     *
    */

    public static function registrationEndTimeCheck($registrationEndTime) {

        if( $registrationEndTime === null ) {
			return true;
		}

        $currentTime = self::getCurrent24TimeString();
		$time1Minutes = self::getMinutesOutOf24TimeString($currentTime);
		$time2Minutes = self::getMinutesOutOf24TimeString($registrationEndTime);

		if( $time1Minutes > $time2Minutes ) {
            return false;
		}

        return true;
    }

    public static function normalizeUnixTimestamp( $unixTimeStamp ) {
        if ( $unixTimeStamp === null ) {
            return null;
        }
    
        // Handle milliseconds (13 digits) vs seconds (10 digits)
        return ( strlen( $unixTimeStamp ) === 13 ) ? (int) ( $unixTimeStamp / 1000 ) : (int) $unixTimeStamp;
    }

    public static function getLocalDateTimeOutOfUnix($unixTimeStamp) {
        if($unixTimeStamp === null) {
            return null;
        }
        $datetime = new DateTime();
        $timestamp = self::normalizeUnixTimestamp( $unixTimeStamp );
        $datetime->setTimestamp($timestamp);
        $datetime->setTimezone(new DateTimeZone( wp_timezone_string() ));

        return $datetime;
    }

    public static function getDateStringFromUnix( $unixTimeStamp ) {
        $timestamp = self::normalizeUnixTimestamp( $unixTimeStamp );

        if ( $timestamp === null ) {
            return null;
        }

        $localizedDate = date_i18n( 'M j Y H:i', $timestamp );
    
        return $localizedDate;
    }
}