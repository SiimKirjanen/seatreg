<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/bookings/SeatregBookingsFile.php');

class SeatregBookingsTxt extends SeatregBookingsFile {

    public function __construct($showPending, $showConfirmed, $separateFirstandLastName, $registrationCode, $calendarDate) {
        parent::__construct($showPending, $showConfirmed, $separateFirstandLastName, $registrationCode, $calendarDate);

        $this->setupTxt();
	}

    private function setupTxt() {
        header("Content-type: text/plain");
        header('Content-Disposition: attachment; filename="'. esc_html($this->_registrationName) . ' '. $this->getFileName().'.txt"');
    }
    
    private function lineBreak() {
        return "\r\n";
    }

    public function printTxt() {
        $customFieldsLength = count($this->_customFields);

        echo esc_html($this->_registrationName), esc_html($this->lineBreak());
        echo esc_html__('Date', 'seatreg'), ': ', esc_html(date('Y-M-d H:i:s e', $this->_currentTimestamp)), esc_html($this->lineBreak()), esc_html($this->lineBreak());
        $placeNumberText = $this->_usingSeats ? esc_html__('Seat number', 'seatreg') : esc_html__('Place number', 'seatreg');

        foreach ($this->_registrations as $registration) {
            $registrantCustomData = json_decode($registration->custom_field_data, true);
            $status = $this->getStatus($registration->status);
            $bookingDate = $this->getBookingDate($registration->booking_date);
            $seatPrice = SeatregLayoutService::getSeatPriceFromLayout($registration, $this->_roomData);
            $usedCouponString = SeatregCouponService::getAppliedCouponString(json_decode($registration->applied_coupon) ?? null);

            echo esc_html($placeNumberText), ': ', esc_html($registration->seat_nr), esc_html($this->lineBreak());
            echo esc_html__('Room', 'seatreg'), ': ', esc_html($registration->room_name), esc_html($this->lineBreak());
            if ( $this->_separateFirstandLastName ) {
                echo esc_html__('First name', 'seatreg'), ': ', esc_html($registration->first_name), esc_html($this->lineBreak());
                echo esc_html__('Last name', 'seatreg'), ': ', esc_html($registration->last_name), esc_html($this->lineBreak());
            } else {
                echo esc_html__('Name', 'seatreg'), ': ', esc_html($registration->first_name), ' ', esc_html($registration->last_name), esc_html($this->lineBreak());
            }
            echo esc_html__('Email', 'seatreg'), ': ', esc_html($registration->email), esc_html($this->lineBreak());
            echo esc_html__('Registration date', 'seatreg'), ': ', esc_html($bookingDate), esc_html($this->lineBreak());

            if( $seatPrice ) {
                $priceDescription = $seatPrice->description ? '('. $seatPrice->description . ')' : '';
                echo esc_html__('Price', 'seatreg'), ': ', esc_html($seatPrice->price), ' ', esc_html($this->_registrationInfo->paypal_currency_code), ' ', esc_html($priceDescription), esc_html($this->lineBreak());
            }

            if($this->_calendarDate) {
                echo esc_html__('Calendar date', 'seatreg'), ': ',  esc_html($this->_calendarDate), esc_html($this->lineBreak());
            }

            echo esc_html__('Booking id', 'seatreg'), ': ', esc_html($registration->booking_id), esc_html($this->lineBreak());
            echo esc_html__('Booking status', 'seatreg'), ': ', esc_html($status), esc_html($this->lineBreak());

            if($status === "Approved") {
                $confirmDate = $this->getBookingDate($registration->booking_confirm_date);
 
                echo esc_html__('Booking approval date', 'seatreg'), ': ', esc_html($confirmDate), esc_html($this->lineBreak());
            }

            echo esc_html__('Used coupon', 'seatreg'), ': ', esc_html($usedCouponString), esc_html($this->lineBreak());
            
            if($registration->payment_status != null) {
                echo esc_html__('Payment status', 'seatreg'), ': ', esc_html($registration->payment_status), esc_html($this->lineBreak());
  
                if($registration->payment_status == SEATREG_PAYMENT_COMPLETED) {
                    echo esc_html__('Payment txn id', 'seatreg'), ': ', esc_html($registration->payment_txn_id), esc_html($this->lineBreak());
                    echo esc_html__('Payment received', 'seatreg'), ': ', esc_html($registration->payment_total_price) . ' ' . esc_html($registration->payment_currency), esc_html($this->lineBreak());
                }
            }
        
            foreach ($this->_customFields as $customField) {
                echo esc_html($this->customFieldsWithValues($customField, $registrantCustomData)), esc_html($this->lineBreak());
            }
        
            echo esc_html($this->lineBreak()), esc_html($this->lineBreak());
        }
    }
}