<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregPaymentRepository {
    /**
     *
     * Return payment object by the booking id
     *
     * @param string $bookingId The booking id
     * @return  array|object|null|void
     *
     */
    public static function getPaymentByBookingId($bookingId) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg_payments
             WHERE booking_id = %s",
            $bookingId
        ) );
    }

    public static function getProcessedPaymentsByBookingId($bookingId) {
        global $seatreg_db_table_names;
	    global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg_payments
            WHERE booking_id = %s
            AND payment_status = %s",
            $bookingId,
            SEATREG_PAYMENT_COMPLETED
        ) );
    }
}