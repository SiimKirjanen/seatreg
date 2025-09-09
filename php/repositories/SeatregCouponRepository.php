<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregCouponRepository {
    public static function areCouponsEnabled($registrationCode) {
        global $wpdb;
        global $seatreg_db_table_names;

        $couponsEnabled = $wpdb->get_var( $wpdb->prepare(
            "SELECT enable_coupons FROM $seatreg_db_table_names->table_seatreg_options
            WHERE registration_code = %s",
            $registrationCode
        ) );

        return (bool)$couponsEnabled;
    }
}