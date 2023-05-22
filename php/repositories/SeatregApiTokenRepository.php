<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregApiTokenRepository {

    public static function getApiToken($apiToken) {
        global $seatreg_db_table_names;
	    global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $seatreg_db_table_names->table_seatreg_api_tokens
            WHERE api_token = %s",
            $apiToken
        ) );
    }
}