<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregCouponService {

    public static function isCouponCodeValid($couponCode) {
        return preg_match(SEATREG_COUPON_CODE_REGEX, $couponCode);
    }

    public static function getAppliedCouponString($appliedCoupon, $currencyCode = null) {
        if( is_null($appliedCoupon) ) {
            return esc_html__('None', 'seatreg');
        }

        return "{$appliedCoupon->couponCode} (-{$appliedCoupon->discountValue}{$currencyCode})";
    }
}