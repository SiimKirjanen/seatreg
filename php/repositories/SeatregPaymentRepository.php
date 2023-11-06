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

    /**
     *
     * Check if there are custom payments. Return true or false.
     *
     * @param object $bookingData The booking data
     * @return boolean
     *
     */
    public static function hasCustomPayments($bookingData) {
        $customPayments  = json_decode( isset($bookingData->custom_payments) ? $bookingData->custom_payments : "[]");

        return count($customPayments) > 0;
    }

    /**
     *
     * Check if at least one payment method is enabled. Return true or false.
     *
     * @param object $bookingData The booking data
     * @return boolean
     *
     */
    public static function hasPaymentEnabled($bookingData) {
        return $bookingData->paypal_payments === '1' || $bookingData->stripe_payments === '1' || $bookingData->custom_payment === '1' || self::hasCustomPayments($bookingData);
    }
}