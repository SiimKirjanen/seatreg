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
                    foreach($bookingsToBeDeleted as $bookingToBeDeleted) {
                        SeatregBookingService::deleteBooking($bookingToBeDeleted->booking_id);
                    }
                }
            }
        }
    }
}