<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregBookingRepository {
    /**
     *
     * Return bookings by the booking ID that are confirmed or approved.
     *
     * @param string $bookingId The ID of the booking
     * @return  array|object|null
     *
     */
    public static function getBookingsById($bookingId) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE booking_id = %s
			AND status != 0",
			$bookingId
		) );
    }

    /**
     *
     * Return data related to booking (registration, registration options)
     *
     * @param string $bookingId The ID of the booking
     * @return  array|object|null|void
     *
     */
    public static function getDataRelatedToBooking($bookingId) {
        global $wpdb;
        global $seatreg_db_table_names;

        $data = $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, b.*
            FROM $seatreg_db_table_names->table_seatreg AS a
            INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
            ON a.registration_code = b.registration_code
            WHERE a.registration_code = (SELECT registration_code FROM $seatreg_db_table_names->table_seatreg_bookings WHERE booking_id = %s LIMIT 1)",
            $bookingId
        ) );

        if($data) {
            $payment = SeatregPaymentRepository::getPaymentByBookingId($bookingId);
    
            if($payment) {
                $data->payment_status = $payment->payment_status;
            }else {
                $data->payment_status = null;
            }
        }

        return $data;
    }
}