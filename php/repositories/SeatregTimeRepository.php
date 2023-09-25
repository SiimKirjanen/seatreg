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
            $registrationOpenClosingText = sprintf( esc_html__('Registration is open %s (%s timezone)'),  $registrationStartTime .' - '. $registrationEndTime, $timeZoneString);
        }else if( $registrationStartTime && $registrationEndTime === null ) {
            $registrationOpenClosingText = sprintf( esc_html__('Registration opens at %s (%s timezone)'),  $registrationStartTime, $timeZoneString);
        }else if( $registrationStartTime === null  && $registrationEndTime ) {
            $registrationOpenClosingText = sprintf( esc_html__('Registration was closed at %s (%s timezone)'),  $registrationEndTime, $timeZoneString);
        }

        return (object)[
            'registrationStartTime' => $registrationStartTime,
            'registrationEndtTime' => $registrationEndTime,
            'registrationStartCheck' => SeatregTimeService::registrationStartTimeCheck($registrationStartTime),
            'registrationEndCheck' => SeatregTimeService::registrationEndTimeCheck($registrationEndTime),
            'registrationOpenClosingText' => $registrationOpenClosingText
        ];
    }

}