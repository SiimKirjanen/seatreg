<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregRegistrationStatusService {
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ENDED = 'ended';

    public static function getStatus($registration) {
        if( $registration->registration_open !== null && (int)$registration->registration_open === 0 ) {
            return self::STATUS_CLOSED;
        }

        $timeStatus = seatreg_registration_time_status(
            $registration->registration_start_timestamp,
            $registration->registration_end_timestamp
        );

        if( $timeStatus === 'wait' ) {
            return self::STATUS_SCHEDULED;
        }

        if( $timeStatus === 'end' ) {
            return self::STATUS_ENDED;
        }

        return self::STATUS_OPEN;
    }

    public static function getStatusLabel($status) {
        switch($status) {
            case self::STATUS_CLOSED:
                return __('Closed', 'seatreg');
            case self::STATUS_SCHEDULED:
                return __('Scheduled', 'seatreg');
            case self::STATUS_ENDED:
                return __('Ended', 'seatreg');
            default:
                return __('Open', 'seatreg');
        }
    }
}
