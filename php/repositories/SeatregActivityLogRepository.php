<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregActivityLogRepository {
    /**
     *
     * Return booking activity logs
     *
     * @param string $bookingId Booking id
     *
     */
    public static function getBookingActivityLogsByBookingId($bookingId) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg_activity_log
            WHERE log_type = 'booking'
            AND relation_id = %s
            ORDER BY log_date DESC",
            $bookingId
        ) );
    }

    /**
     *
     * Return registration activity logs (map and settigns updates)
     *
     * @param string $registrationId The id of registration
     *
     */
    public static function getRegistrationAcitivityLogs($registrationId) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg_activity_log
            WHERE log_type IN ('map', 'settings')
            AND relation_id = %s
            ORDER BY log_date DESC",
            $registrationId
        ) );
    }
}