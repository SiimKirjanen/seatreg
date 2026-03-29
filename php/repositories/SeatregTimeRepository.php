<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregTimeRepository {
    /**
     *
     * Get time related data for registration view
     * @param string|null $registrationStartTime representation of 24h time ('H:i')
     * @param string|null $registrationEndTime representation of 24h time ('H:i')
     *
     */
    public static function getTimeInfoForRegistrationView($registrationStartTime, $registrationEndTime) {
        $registrationOpenClosingText = null;
        $timeZoneString = wp_timezone_string();

        if( $registrationStartTime && $registrationEndTime ) {
            /* translators: 1: registration start time, 2: registration end time, 3: timezone */
            $registrationOpenClosingText = sprintf( esc_html__('Registration is open %1$s - %2$s (%3$s timezone)', 'seatreg'),  $registrationStartTime, $registrationEndTime, $timeZoneString);
        }else if( $registrationStartTime && $registrationEndTime === null ) {
            /* translators: 1: registration start time, 2: timezone */
            $registrationOpenClosingText = sprintf( esc_html__('Registration opens at %1$s (%2$s timezone)', 'seatreg'),  $registrationStartTime, $timeZoneString);
        }else if( $registrationStartTime === null  && $registrationEndTime ) {
            /* translators: 1: registration end time, 2: timezone */
            $registrationOpenClosingText = sprintf( esc_html__('Registration was closed at %1$s (%2$s timezone)', 'seatreg'),  $registrationEndTime, $timeZoneString);
        }

        return (object)[
            'registrationStartTime' => $registrationStartTime,
            'registrationEndtTime' => $registrationEndTime,
            'registrationStartCheck' => SeatregTimeService::registrationStartTimeCheck($registrationStartTime),
            'registrationEndCheck' => SeatregTimeService::registrationEndTimeCheck($registrationEndTime),
            'registrationOpenClosingText' => $registrationOpenClosingText,
            'timezone' => $timeZoneString
        ];
    }

}