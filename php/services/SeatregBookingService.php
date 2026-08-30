<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregBookingService {
    //Kept inline rather than in the email stylesheet because several email clients strip <style> blocks.
    const EMAIL_TABLE_STYLE = 'width:100%;max-width:100%;border-collapse:collapse;table-layout:fixed;margin:0 0 12px 0;';
    const EMAIL_TABLE_HEADER_STYLE = 'text-align:left;padding:6px 8px;border:1px solid #e2e8f0;background-color:#f1f4f9;font-weight:600;word-break:break-word;overflow-wrap:break-word;';
    const EMAIL_TABLE_VALUE_STYLE = 'padding:6px 8px;border:1px solid #e2e8f0;word-break:break-word;overflow-wrap:break-word;';

    //Same look for the tables on the booking status page, but sized to their content instead of a fixed email width.
    const PAGE_TABLE_STYLE = 'border-collapse:collapse;margin:0 auto;';
    const PAGE_TABLE_HEADER_STYLE = 'text-align:left;padding:8px 10px;border:1px solid #e2e8f0;background-color:#f1f4f9;font-weight:600;white-space:nowrap;';
    const PAGE_TABLE_VALUE_STYLE = 'padding:8px 10px;border:1px solid #e2e8f0;';

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

        return $wpdb->update(
			$seatreg_db_table_names->table_seatreg_bookings,
			array(
				'is_deleted' => 1,
				'deletion_reason' => 'Booking expired and was automatically removed'
			),
			array('booking_id' => $bookingId),
			array('%d', '%s'),
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
     * Set booking approved and record the approval date
     * @param string $bookingId The UUID of the booking
     * @return (int|false) The number of rows updated, or false on error.
     *
    */
    public static function setBookingApproved($bookingId) {
        global $seatreg_db_table_names;
		global $wpdb;

        return $wpdb->update(
			$seatreg_db_table_names->table_seatreg_bookings,
			array(
				'status' => SEATREG_BOOKING_APPROVED,
				'booking_confirm_date' => time()
			),
			array(
				'booking_id' => $bookingId
			),
			'%s'
		);
    }

    /**
     *
     * Collect the columns and values shown for a booking
     * @param array $registrationCustomFields custom fields added to registration
     * @param array $bookings The UUID of the booking
     * @param object $registration Registration data
     * @return array 'headers' => column labels, 'rows' => label/value pairs per booking. Values are unescaped
     *
    */
    private static function getBookingTableData($registrationCustomFields, $bookings, $registration) {
        $enteredCustomFieldData = json_decode($bookings[0]->custom_field_data);
        $customFieldLabels = array_map(function($customField) {
            return $customField->label;
        }, is_array( $enteredCustomFieldData) ? $enteredCustomFieldData : [] );
        $spotName = $registration->using_seats ? __('Seat', 'seatreg') : __('Place', 'seatreg');
        $hasCalendarDate = (boolean)$bookings[0]->calendar_date;
        $roomsLayout = SeatregLayoutService::getRoomDataFromLayout($registration->registration_layout ?? null);
        $seatLegends = array();

        foreach($bookings as $bookingKey => $booking) {
            $seatLegends[$bookingKey] = SeatregRegistrationService::getSeatLegendFromLayout($roomsLayout, $booking->room_uuid, $booking->seat_id);
        }

        $hasLegends = count(array_filter($seatLegends)) > 0;
        $headers = array( __('Name', 'seatreg'), $spotName, __('Room', 'seatreg') );

        if($hasLegends) {
            $headers[] = __('Label', 'seatreg');
        }

        $headers[] = __('Email', 'seatreg');

        if($hasCalendarDate) {
            $headers[] = __('Calendar date', 'seatreg');
        }

        foreach($customFieldLabels as $customFieldLabel) {
            $headers[] = $customFieldLabel;
        }

        $rows = array();

        foreach ($bookings as $bookingKey => $booking) {
            $bookingCustomFields = json_decode($booking->custom_field_data);
            $row = array(
                array( 'label' => __('Name', 'seatreg'), 'value' => $booking->first_name . ' ' . $booking->last_name ),
                array( 'label' => $spotName, 'value' => $booking->seat_nr ),
                array( 'label' => __('Room', 'seatreg'), 'value' => $booking->room_name ),
            );

            if($hasLegends) {
                $row[] = array( 'label' => __('Label', 'seatreg'), 'value' => $seatLegends[$bookingKey] );
            }

            $row[] = array( 'label' => __('Email', 'seatreg'), 'value' => $booking->email );

            if($hasCalendarDate) {
                $row[] = array( 'label' => __('Calendar date', 'seatreg'), 'value' => $booking->calendar_date );
            }

            if( is_array($bookingCustomFields) ) {
                foreach($bookingCustomFields as $bookingCustomField) {
                    $valueToDisplay = $bookingCustomField->value;

                    $customFieldObject = array_values(array_filter($registrationCustomFields, function($custField) use($bookingCustomField) {
                        return $custField->label === $bookingCustomField->label;
                    }));

                    if( count($customFieldObject) > 0 && $customFieldObject[0]->type === 'check' ) {
                        $valueToDisplay = $bookingCustomField->value === '1' ? __('Yes', 'seatreg') : __('No', 'seatreg');
                    }

                    $row[] = array( 'label' => $bookingCustomField->label, 'value' => $valueToDisplay );
                }
            }

            $rows[] = $row;
        }

        return array(
            'headers' => $headers,
            'rows' => $rows,
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
        $tableData = self::getBookingTableData($registrationCustomFields, $bookings, $registration);
        $bookingTable = '<table style="' . self::PAGE_TABLE_STYLE . '"><tr>';

        foreach($tableData['headers'] as $header) {
            $bookingTable .= '<th style="' . self::PAGE_TABLE_HEADER_STYLE . '">' . esc_html($header) . '</th>';
        }

        $bookingTable .= '</tr>';

        foreach($tableData['rows'] as $row) {
            $bookingTable .= '<tr>';

            foreach($row as $field) {
                $bookingTable .= '<td style="' . self::PAGE_TABLE_VALUE_STYLE . '">' . esc_html($field['value'] ?? '') . '</td>';
            }

            $bookingTable .= '</tr>';
        }

        $bookingTable .= '</table>';

        return $bookingTable;
    }

    /**
     *
     * Generate booking table for emails. One row per field, so it fits the fixed width email card
     * no matter how many custom fields the registration has
     * @param array $registrationCustomFields custom fields added to registration
     * @param array $bookings The UUID of the booking
     * @param object $registration Registration data
     * @return string Booking table markup
     *
    */
    public static function generateEmailBookingTable($registrationCustomFields, $bookings, $registration) {
        $tableData = self::getBookingTableData($registrationCustomFields, $bookings, $registration);
        $bookingTable = '<div style="margin: 12px 0;">';

        foreach($tableData['rows'] as $row) {
            $bookingTable .= '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="' . self::EMAIL_TABLE_STYLE . '">';

            foreach($row as $field) {
                $bookingTable .= '<tr>';
                $bookingTable .= '<th style="width:38%;' . self::EMAIL_TABLE_HEADER_STYLE . '">' . esc_html($field['label']) . '</th>';
                $bookingTable .= '<td style="' . self::EMAIL_TABLE_VALUE_STYLE . '">' . esc_html($field['value'] ?? '') . '</td>';
                $bookingTable .= '</tr>';
            }

            $bookingTable .= '</table>';
        }

        $bookingTable .= '</div>';

        return $bookingTable;
    }

    /**
     *
     * Collect the seats, their prices and the total cost of a booking
     * @param string $bookingId booking id
     * @param bool $couponsEnabled whether coupons are enabled for the registration
     * @param object $appliedCoupon coupon applied to the booking
     * @return array 'spotName', 'rows' => seat/price pairs, 'total'. Values are unescaped
     *
    */
    private static function getPaymentTableData($bookingId, $couponsEnabled = false, $appliedCoupon = null) {
        $bookingData = SeatregBookingRepository::getDataRelatedToBooking($bookingId);
        $bookings = self::getBookingsCost($bookingId, $bookingData->registration_layout);
        $totalCost = 0;
        $rows = array();

        foreach($bookings as $booking) {
            $totalCost += $booking->price;
            $priceDescription = $booking->description ? "($booking->description)" : null;

            $rows[] = array(
                'seatNr' => $booking->seatNr,
                'price' => $booking->price . ' ' . $bookingData->paypal_currency_code . ' ' . $priceDescription,
            );
        }

        return array(
            'spotName' => $bookingData->using_seats ? __('Seat', 'seatreg') : __('Place', 'seatreg'),
            'rows' => $rows,
            'total' => ( $couponsEnabled ? self::applyCouponDiscountToTotalCost($totalCost, $appliedCoupon) : $totalCost ) . ' ' . $bookingData->paypal_currency_code,
        );
    }

    public static function generatePaymentTable($bookingId, $couponsEnabled = false, $appliedCoupon = null) {
        $tableData = self::getPaymentTableData($bookingId, $couponsEnabled, $appliedCoupon);
        $paymentTable = '<table style="' . self::PAGE_TABLE_STYLE . '">
            <tr>
                <th style="' . self::PAGE_TABLE_HEADER_STYLE . '">' . esc_html($tableData['spotName']) . '</th>
                <th style="' . self::PAGE_TABLE_HEADER_STYLE . '">' . esc_html__('Price', 'seatreg') . '</th>
            </tr>';

        foreach($tableData['rows'] as $row) {
            $paymentTable .= '<tr>';
                $paymentTable .= '<td style="' . self::PAGE_TABLE_VALUE_STYLE . '">'. esc_html($row['seatNr']) .'</td>';
                $paymentTable .= '<td style="' . self::PAGE_TABLE_VALUE_STYLE . '">'. esc_html($row['price']) . '</td>';
            $paymentTable .= '</tr>';
        }

        $paymentTable .= '<tr>';
            $paymentTable .= '<td style="' . self::PAGE_TABLE_VALUE_STYLE . 'font-weight:700;">'.  esc_html__('Total', 'seatreg') .'</td>';
            $paymentTable .= '<td style="' . self::PAGE_TABLE_VALUE_STYLE . 'font-weight:700;">'. esc_html($tableData['total']) . '</td>';
        $paymentTable .= '</tr>';

        $paymentTable .= '</table>';

        return $paymentTable;
    }

    public static function generateEmailPaymentTable($bookingId, $couponsEnabled = false, $appliedCoupon = null) {
        $tableData = self::getPaymentTableData($bookingId, $couponsEnabled, $appliedCoupon);
        $paymentTable = '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="' . self::EMAIL_TABLE_STYLE . '">
            <tr>
                <th style="' . self::EMAIL_TABLE_HEADER_STYLE . '">' . esc_html($tableData['spotName']) . '</th>
                <th style="' . self::EMAIL_TABLE_HEADER_STYLE . '">' . esc_html__('Price', 'seatreg') . '</th>
            </tr>';

        foreach($tableData['rows'] as $row) {
            $paymentTable .= '<tr>';
                $paymentTable .= '<td style="' . self::EMAIL_TABLE_VALUE_STYLE . '">'. esc_html($row['seatNr']) .'</td>';
                $paymentTable .= '<td style="' . self::EMAIL_TABLE_VALUE_STYLE . '">'. esc_html($row['price']) . '</td>';
            $paymentTable .= '</tr>';
        }

        $paymentTable .= '<tr>';
            $paymentTable .= '<td style="' . self::EMAIL_TABLE_VALUE_STYLE . 'font-weight:700;">'.  esc_html__('Total', 'seatreg') .'</td>';
            $paymentTable .= '<td style="' . self::EMAIL_TABLE_VALUE_STYLE . 'font-weight:700;">'. esc_html($tableData['total']) . '</td>';
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
     * Check if booking has a payment that prevents it from being deleted by pending booking expiration.
     * A payment is "blocking" when its status is not in the deletable set. With an empty deletable set
     * any payment row is blocking
     * @param string $bookingId booking id
     * @param array $deletablePaymentStatuses payment statuses that are allowed to be deleted
     * @return bool
     *
    */
    public static function checkIfBookingHasNonExpirablePayment($bookingId, $deletablePaymentStatuses = array()) {
        global $wpdb;
        global $seatreg_db_table_names;

        if( count($deletablePaymentStatuses) > 0 ) {
            $placeholders = implode(', ', array_fill(0, count($deletablePaymentStatuses), '%s'));
            $result = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $seatreg_db_table_names->table_seatreg_payments
                WHERE booking_id = %s
                AND payment_status NOT IN ($placeholders)",
                array_merge( array($bookingId), $deletablePaymentStatuses )
            ));
        }else {
            $result = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $seatreg_db_table_names->table_seatreg_payments
                WHERE booking_id = %s",
                $bookingId
            ));
        }

        if($result) {
            return true;
        }

        return false;
    }

    public static function checkIfSeatAlreadyBooked($seatId, $seatNr, $existingBookings) {	
            $statusReport = (object) ['is_valid' => true, 'messages' => []];
            $bookingsLength = count($existingBookings);
    
            for($i = 0; $i < $bookingsLength; $i++) {
            
                if( $existingBookings[$i]->seat_id == $seatId) {
                    $statusReport->is_valid = false;
                    $statusReport->messages[] = 'Seat '. esc_html($seatNr) . ' with ID ' . $seatId . ' is already booked';
    
                    break;
                }
                
            } 
    
        return $statusReport;
    }

    public static function updateBookingCustomTextForApprovedEmail($bookingId, $customText) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->update( 
            $seatreg_db_table_names->table_seatreg_bookings,
            array( 
                'custom_text_for_approved_email' => $customText,
            ), 
            array(
                'booking_id' => $bookingId
            ),
            '%s'
        );
    }

    public static function applyCouponDiscountToTotalCost($totalCost, $coupon) {
        if( !$coupon ) {
            return $totalCost;
        }

        $bookingPrice = $totalCost - $coupon->discountValue;

        return $bookingPrice;
    }
}