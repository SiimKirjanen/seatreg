<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregBookingService {
    /**
     *
     * Return booking total cost
     *
    */
    public static function getBookingTotalCost($bookingId, $registrationLayout) {
        $bookings = SeatregBookingRepository::getBookingsById($bookingId);
        $roomsData = json_decode($registrationLayout)->roomData;
        $totalCost = 0;
    
        foreach($bookings as $booking) {
            $seatPrice = SeatregRegistrationService::getSeatPriceFromLayout($booking->seat_id, $booking->room_uuid, $roomsData);
            $totalCost += $seatPrice;
        }
    
        return $totalCost;
    }
}