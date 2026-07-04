<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregJobService {
    /**
     *
     * Job that cleans pending bookings if turned on by registration
     *
    */
    public static function pendingBookingExpirationTimeJob() {
        $registrations = SeatregRegistrationRepository::getRegistrationsWherePendingBookingExpirationIsSet();

        if($registrations) {
            foreach($registrations as $registration) {
                $pendingBookingExpirationTime = (int)$registration->pending_expiration;
                $deletablePaymentStatuses = $registration->pending_expiration_payment_statuses ? array_filter(explode(',', $registration->pending_expiration_payment_statuses)) : array();
                $bookingsToBeDeleted = SeatregBookingRepository::getPendingBookingsThatAreExpired($registration->registration_code, $pendingBookingExpirationTime, $deletablePaymentStatuses);

                if($bookingsToBeDeleted) {
                    $layout = json_decode($registration->registration_layout);
                    $roomData = $layout ? $layout->roomData : array();
                    $bookingsGroupedById = array();

                    foreach($bookingsToBeDeleted as $bookingToBeDeleted) {
                        $bookingsGroupedById[$bookingToBeDeleted->booking_id][] = $bookingToBeDeleted;
                    }

                    foreach($bookingsGroupedById as $bookingId => $bookingSeats) {
                        $message = self::buildExpiredBookingLogMessage($bookingSeats, $roomData);

                        seatreg_add_activity_log('booking_expiration', $registration->registration_code, $message, false);
                        SeatregBookingService::deleteBooking($bookingId);
                    }
                }
            }
        }
    }

    /**
     *
     * Build the activity log message for a pending booking that expired and is being deleted.
     *
     * @param array $bookingSeats Seat rows that share the same booking_id
     * @param array $roomData Registration layout roomData used to resolve room names
     * @return string
     *
    */
    private static function buildExpiredBookingLogMessage($bookingSeats, $roomData) {
        $firstSeat = $bookingSeats[0];
        $seatDescriptions = array();

        foreach($bookingSeats as $seat) {
            $roomName = SeatregRegistrationService::getRoomNameFromLayout($roomData, $seat->room_uuid);
            $seatDescriptions[] = ($roomName ? $roomName : 'Unknown room') . ' - ' . $seat->seat_nr;
        }

        return "Pending booking expired and was automatically deleted by the pending booking expiration job. Booker email: $firstSeat->booker_email. "
            . "Seats: " . implode(', ', $seatDescriptions) . ". Booking id: $firstSeat->booking_id";
    }
}