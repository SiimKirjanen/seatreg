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

    public static function getCoupons($registrationCode) {
        global $wpdb;
        global $seatreg_db_table_names;

        $coupons = $wpdb->get_var( $wpdb->prepare(
            "SELECT coupons FROM $seatreg_db_table_names->table_seatreg_options
            WHERE registration_code = %s",
            $registrationCode
        ) );

        if ($coupons === null) {
            return [];
        }

        $decoded = json_decode($coupons);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $decoded;
    }

    public static function findCoupon($registrationCode, $couponCodeToFind) {
        $coupons = self::getCoupons($registrationCode);

        foreach ($coupons as $coupon) {
            if (isset($coupon->couponCode) && $coupon->couponCode === $couponCodeToFind) {
                return $coupon;
            }
        }

        return null;
    }
}