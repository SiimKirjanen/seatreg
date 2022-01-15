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
                $bookingsToBeDeleted = SeatregBookingRepository::getPendingBookingsThatAreExpired($registration->registration_code, $pendingBookingExpirationTime);

                if($bookingsToBeDeleted) {
                    foreach($bookingsToBeDeleted as $bookingToBeDeleted) {
                        SeatregBookingService::deleteBooking($bookingToBeDeleted->booking_id);
                    }
                }
            }
        }
    }
}