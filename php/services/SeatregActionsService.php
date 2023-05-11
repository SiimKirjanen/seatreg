<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregActionsService {
    public static function triggerBookingSubmittedAction($bookingId) {
        do_action(SEATREG_ACTION_BOOKING_SUBMITTED, $bookingId);
    }
    public static function triggerBookingPendingAction($bookingId) {
        do_action(SEATREG_ACTION_BOOKING_PENDING, $bookingId);
    }
    public static function triggerBookingApprovedAction($bookingId) {
        do_action(SEATREG_ACTION_BOOKING_APPROVED, $bookingId);
    }
}