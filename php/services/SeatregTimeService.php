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
     * Get minutes our of 24h time string
     * @param string $time String representation of 24h time ('H:i')
     * @return int Return number of minutes
     *
    */
    public static function getMinutesOutOf24TimeString($time) {
        return (intval(substr($time, 0, 2)) * 60) + intval(substr($time, 3, 2));
    }
}