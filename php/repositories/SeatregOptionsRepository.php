<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregOptionsRepository {
    /**
     *
     * Return options by registration code
     *
     * @param string $registrationCode The code of registration
     *
     */
    public static function getOptionsByRegistrationCode($registrationCode) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_options
			WHERE registration_code = %s",
			$registrationCode
		) );
    }
}