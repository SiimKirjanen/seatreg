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
     * Return confirmed and approved bookings but registration code
     *
     * @param string $registrationCode The code of registration
     *
     */
    public static function getConfirmedAndApprovedBookingsByRegistrationCode($registrationCode) {
        global $wpdb;
	    global $seatreg_db_table_names;

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
            WHERE registration_code = %s
            AND (status = '1' OR status = '2')",
            $registrationCode
        ) );
    } 

    /**
     *
     * Return bookings by conf code.
     *
     * @param string $confCode The conf code of the booking
     *
     */
    public static function getBookingByConfCode($confCode) {
        global $wpdb;
        global $seatreg_db_table_names;
        
        return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE conf_code = %s
			AND status = 0",
			$confCode
		) );
    }

    /**
     *
     * Return bookings by registration code and booking id
     *
     * @param string $registrationCode The code of registration
     * @param string $bookingId The id of booking
     *
     */
    public static function getBookingsByRegistrationCodeAndBookingId($registrationCode, $bookingId) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE registration_code = %s
			AND booking_id = %s
			AND status != 0",
			$registrationCode,
			$bookingId
		) );
    }

    /**
     *
     * Return data related to booking (registration, registration options)
     *
     * @param string $bookingId The ID of the booking
     * @return array|object|null|void
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

    /**
     *
     * Return registration pending bookings where registration time is older than expiration time. If booking has some payment related entries then dont include it.
     *
     * @param string $registrationCode The registration code
     * @param int $expirationTimeInMinutes The expiration time set in registration settings. In minutes
     * @return (array|object|null)
     *
     */
    public static function getPendingBookingsThatAreExpired($registrationCode, $expirationTimeInMinutes) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings AS a
            WHERE a.registration_code = %s
            AND a.status = 1
            AND ((UNIX_TIMESTAMP() - a.booking_date) / 60) > %d
            AND (SELECT COUNT(*) FROM $seatreg_db_table_names->table_seatreg_payments AS b WHERE b.booking_id = a.booking_id) = 0",
            $registrationCode,
            $expirationTimeInMinutes
        ) );
    }
}