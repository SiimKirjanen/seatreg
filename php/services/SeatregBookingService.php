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

     /**
     *
     * Delete a booking
     * @param string $bookingId The UUID of the booking
     * @return (int|false) The number of rows updated, or false on error.
     *
    */
    public static function deleteBooking($bookingId) {
        global $seatreg_db_table_names;
	    global $wpdb;

        return $wpdb->delete( 
			$seatreg_db_table_names->table_seatreg_bookings,
			array('booking_id' => $bookingId), 
			'%s'
		);
    }

    /**
     *
     * Change booking status
     * @param int $status booking status
     * @param string $bookingId The UUID of the booking
     * @return (int|false) The number of rows updated, or false on error.
     * 
    */
    public static function changeBookingStatus($status, $bookingId) {
        global $seatreg_db_table_names;
		global $wpdb;

        return $wpdb->update( 
			$seatreg_db_table_names->table_seatreg_bookings,
			array( 
				'status' => $status,
			), 
			array(
				'booking_id' => $bookingId
			),
			'%s'
		);
    }
}