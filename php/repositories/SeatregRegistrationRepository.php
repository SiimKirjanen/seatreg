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
     * Return all registrations that are not deleted, with the options needed to show
     * their status. Newest first. Left join, as the options row is written after the
     * registration and a half created one still has to show up.
     *
     * @return (array|object|null)
     *
     */
    public static function getRegistrationsWithStatusOptions() {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_results(
            "SELECT a.id, a.registration_code, a.registration_name, a.registration_create_timestamp, a.registration_layout,
                b.registration_open, b.registration_close_reason,
                b.registration_start_timestamp, b.registration_end_timestamp,
                b.registration_start_time, b.registration_end_time,
                b.using_calendar, b.calendar_dates,
                b.room_noun_singular, b.room_noun_plural
            FROM $seatreg_db_table_names->table_seatreg AS a
            LEFT JOIN $seatreg_db_table_names->table_seatreg_options AS b
            ON a.registration_code = b.registration_code
            WHERE a.is_deleted = 0
            ORDER BY a.registration_create_timestamp DESC, a.id DESC"
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