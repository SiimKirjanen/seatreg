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
            $seatPrice = SeatregLayoutService::getSeatPriceFromLayout($booking, $roomsData);
            $totalCost += $seatPrice->price;
        }
    
        return $totalCost;
    }

    /**
     *
     * Return booked seats and their cost
     *
    */
    public static function getBookingsCost($bookingId, $registrationLayout) {
        $bookings = SeatregBookingRepository::getBookingsById($bookingId);
        $roomsData = json_decode($registrationLayout)->roomData;

        return array_map(function($booking) use($roomsData) {
            $seatPrice = SeatregLayoutService::getSeatPriceFromLayout($booking, $roomsData);
            $priceDescription = null;

            if( $booking->multi_price_selection ) {
               $multiPriceObject = SeatregLayoutService::checkIfMultiPriceUUIDExists($booking, $roomsData);

                if($multiPriceObject) {
                    $priceDescription = $multiPriceObject->description;
                }
            }

            return (object)[
                'seatId' => $booking->seat_id,
                'seatNr' => $booking->seat_nr,
                'price' => $seatPrice->price,
                'description' => $priceDescription
            ];
        }, $bookings);
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

    /**
     *
     * Generate booking table
     * @param array $registrationCustomFields custom fields added to registration
     * @param array $bookings The UUID of the booking
     * @param object $registration Registration data
     * @return string Booking table markup
     * 
    */

    public static function generateBookingTable($registrationCustomFields, $bookings, $registration) {
        $enteredCustomFieldData = json_decode($bookings[0]->custom_field_data);
        $customFieldLabels = array_map(function($customField) {
            return $customField->label;
        }, is_array( $enteredCustomFieldData) ? $enteredCustomFieldData : [] );
        $spotName = $registration->using_seats ? __('Seat', 'seatreg') : __('Place', 'seatreg');
        $hasCalendarDate = (boolean)$bookings[0]->calendar_date;

        $bookingTable = '<table style="border: 1px solid black;border-collapse: collapse;">
            <tr>
            <th style="border:1px solid black;text-align:left;padding: 6px;">' . __('Name', 'seatreg') . '</th>
            <th style="border:1px solid black;text-align:left;padding: 6px;">' . $spotName . '</th>
            <th style="border:1px solid black;text-align:left;padding: 6px;">' . __('Room', 'seatreg') . '</th>
            <th style="border:1px solid black;text-align:left;padding: 6px;">' . __('Email', 'seatreg') . '</th>';

        if($hasCalendarDate) {
            $bookingTable .= '<th style="border:1px solid black;text-align: left;padding: 6px;">' . __('Calendar date', 'seatreg') . '</th>';
        }
        
        foreach($customFieldLabels as $customFieldLabel) {
            $bookingTable .= '<th style="border:1px solid black;text-align: left;padding: 6px;">' . esc_html($customFieldLabel) . '</th>';
        }
        $bookingTable .= '</tr>';

        foreach ($bookings as $booking) {
            $bookingCustomFields = json_decode($booking->custom_field_data);
            $bookingTable .= '<tr>
                <td style="border:1px solid black;padding: 6px;">'. esc_html($booking->first_name . ' ' .  $booking->last_name) .'</td>
                <td style="border:1px solid black;padding: 6px;">'. esc_html($booking->seat_nr) . '</td>
                <td style="border:1px solid black;padding: 6px;">'. esc_html($booking->room_name) . '</td>
                <td style="border:1px solid black;padding: 6px;">'. esc_html($booking->email) . '</td>';

                if($hasCalendarDate) {
                    $bookingTable .= '<td style="border:1px solid black;padding: 6px;">'. esc_html($booking->calendar_date) . '</td>';
                }
    
                if( is_array($bookingCustomFields) ) {
                    foreach($bookingCustomFields as $bookingCustomField) {
                        $valueToDisplay = $bookingCustomField->value;
    
                        $customFieldObject = array_values(array_filter($registrationCustomFields, function($custField) use($bookingCustomField) {
                            return $custField->label === $bookingCustomField->label;
                        }));
        
                        if( count($customFieldObject) > 0 && $customFieldObject[0]->type === 'check' ) {
                            $valueToDisplay = $bookingCustomField->value === '1' ? esc_html__('Yes', 'seatreg') : esc_html__('No', 'seatreg');
                        }
                        $bookingTable .= '<td style="border:1px solid black;padding: 6px;">'. esc_html($valueToDisplay) . '</td>';
                    }
                }
            
            $bookingTable .= '</tr>';
        }

        $bookingTable .= '</table>';

        return $bookingTable;
    }

    public static function generatePaymentTable($bookingId) {
        $bookingData = SeatregBookingRepository::getDataRelatedToBooking($bookingId);
        $bookings = self::getBookingsCost($bookingId, $bookingData->registration_layout);
        $totalCost = 0;
        $spotName = $bookingData->using_seats ? __('Seat', 'seatreg') : __('Place', 'seatreg');
        $paymentTable = '<table style="border: 1px solid black;border-collapse: collapse;">
            <tr>
                <th style=";border:1px solid black;text-align: left;padding: 6px;">' . $spotName . '</th>
                <th style=";border:1px solid black;text-align: left;padding: 6px;">' . __('Price', 'seatreg') . '</th>
            </tr>';

        foreach($bookings as $booking) {
            $totalCost += $booking->price;
            $priceDescription = $booking->description ? "($booking->description)" : null;

            $paymentTable .= '<tr>';
                $paymentTable .= '<td style=";border:1px solid black;padding: 6px;"">'. esc_html($booking->seatNr) .'</td>';
                $paymentTable .= '<td style=";border:1px solid black;padding: 6px;"">'. esc_html($booking->price) . ' ' . $bookingData->paypal_currency_code . ' ' . $priceDescription  . '</td>';
            $paymentTable .= '</tr>';
        }

        $paymentTable .= '<tr>';
            $paymentTable .= '<td style=";border:1px solid black;padding: 6px;font-weight:700">'.  __('Total', 'seatreg') .'</td>';
            $paymentTable .= '<td style=";border:1px solid black;padding: 6px;font-weight:700">'. $totalCost . ' ' . $bookingData->paypal_currency_code . '</td>';
        $paymentTable .= '</tr>';

        $paymentTable .= '</table>';

        return $paymentTable;
    }

    /**
     *
     * Get booking status as text
     * @param string $status booking status
     * @return string Booking status as text
     * 
    */
    public static function getBookingStatusText($status) {
        if($status === '1') {
            return esc_html__('Pending', 'seatreg');
        }else if ($status === '2') {
            return esc_html__('Approved', 'seatreg');
        }
    }

    /**
     *
     * Check if booking has entry in payments table
     * @param string $bookingId booking id
     * @return bool
     * 
    */
    public static function checkIfBookingHasPaymentEntry($bookingId) {
        global $wpdb;
        global $seatreg_db_table_names;

        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $seatreg_db_table_names->table_seatreg_payments
            WHERE booking_id = %s",
            $bookingId
        ));

        if($result) {
            return true;
        }

        return false;
    }
}