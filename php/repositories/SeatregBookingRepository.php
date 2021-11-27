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
}