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
    public static function triggerBookingPendingActionViaManager($bookingId) {
        do_action(SEATREG_ACTION_BOOKING_PENDING_VIA_MANAGER, $bookingId);
    }
    public static function triggerBookingApprovedAction($bookingId) {
        do_action(SEATREG_ACTION_BOOKING_APPROVED, $bookingId);
    }
    public static function triggerBookingApprovedActionViaManager($bookingId) {
        do_action(SEATREG_ACTION_BOOKING_APPROVED_VIA_MANAGER, $bookingId);
    }
    public static function triggerBookingRemovedAction($bookingId) {
        do_action(SEATREG_ACTION_BOOKING_REMOVED, $bookingId);
    }
    public static function triggerBookingManuallyAddedAction($bookingId) {
        do_action(SEATREG_ACTION_BOOKING_MANUALLY_ADDED, $bookingId);
    }
}