<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregPaymentLogRepository {
    /**
     *
     * Return booking payment logs
     *
     * @param string $bookingId The id of the booking
     *
     */
    public static function getPaymentLogsByBookingId($bookingId) {
        global $seatreg_db_table_names;
	    global $wpdb;

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg_payments_log
            WHERE booking_id = %s",
            $bookingId
        ) );
    }
}