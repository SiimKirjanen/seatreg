<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregRegistrationRepository {
    /**
     *
     * Return registration object by registration code
     *
     */
    public static function getRegistrationByCode($registrationCode) {
        global $wpdb;
	    global $seatreg_db_table_names;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg
            WHERE registration_code = %s",
            $registrationCode
        ) );
    }


    public static function getRegistrationLayout($registrationCode) {
        $registration = self::getRegistrationByCode($registrationCode);

        return json_decode( $registration->registration_layout );
    }

     /**
     *
     * Return all registrations that are not deleted
     *
     */
    public static function getRegistrations() {
        global $wpdb;
	    global $seatreg_db_table_names;

        return $wpdb->get_results(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg WHERE is_deleted = 0"
        );
    }

    /**
     *
     * Return registration object by the registration code with related options
     *
     * @param string $registrationCode The code of the registration
     * @return  array|object|null|void
     *
     */
    public static function getRegistrationWithOptionsByCode($registrationCode) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, b.*
            FROM $seatreg_db_table_names->table_seatreg AS a
            INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
            ON a.registration_code = b.registration_code
            WHERE a.registration_code = %s",
            $registrationCode
        ) );
    }
    /**
         *
         * Return registrations with options if pending_expiration is set
         *
         * @return (array|object|null)
         *
     */
    public static function getRegistrationsWherePendingBookingExpirationIsSet() {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_results(
            "SELECT a.*, b.*
            FROM $seatreg_db_table_names->table_seatreg AS a
            INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
            ON a.registration_code = b.registration_code
            WHERE b.pending_expiration IS NOT NULL"
        );     
    }
}