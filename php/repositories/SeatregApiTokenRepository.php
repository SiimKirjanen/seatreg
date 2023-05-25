<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregApiTokenRepository {
    public static function getApiToken($apiToken) {
        global $seatreg_db_table_names;
	    global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, b.public_api_enabled 
            FROM $seatreg_db_table_names->table_seatreg_api_tokens AS a
            INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
            ON a.registration_code = b.registration_code
            WHERE a.api_token = %s",
            $apiToken
        ) );
       
    }

    public static function getRegistrationApiTokens($registrationCode) {
        global $seatreg_db_table_names;
	    global $wpdb;

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg_api_tokens
            WHERE registration_code = %s",
            $registrationCode
        ) );
    }
}