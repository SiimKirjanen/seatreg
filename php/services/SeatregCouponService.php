<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregCouponService {

    public static function isCouponCodeValid($couponCode) {
        return preg_match(SEATREG_COUPON_CODE_REGEX, $couponCode);
    }

    public static function getAppliedCouponsString($appliedCoupons) {
        if( empty($appliedCoupons) ) {
            return esc_html__('None', 'seatreg');
        }
        return implode(',', array_map(function($coupon) {
            return $coupon->coupon_code . ' (-' . $coupon->discount . ')';
        }, $appliedCoupons));
    }
}