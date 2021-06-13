<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/bookings/SeatregBookingsFile.php');

class SeatregBookingsTxt extends SeatregBookingsFile {

    public function __construct($showPending, $showConfirmed, $registrationCode) {
        parent::__construct($showPending, $showConfirmed, $registrationCode);

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

        echo esc_html($this->_registrationName), $this->lineBreak();
        echo esc_html__('Date:', 'seatreg'), ' ', date('Y-M-d H:i:s e', $this->_currentTimestamp), $this->lineBreak(), $this->lineBreak();

        foreach ($this->_registrations as $registration) {
            $registrantCustomData = json_decode($registration->custom_field_data, true);
            $status = $this->getStatus($registration->status);
            $bookingDate = $this->getBookingDate($registration->booking_date);

            echo esc_html__('Seat nr:', 'seatreg'), ' ', esc_html($registration->seat_nr), $this->lineBreak();
            echo esc_html__('Room:', 'seatreg'), ' ', esc_html($registration->room_name), $this->lineBreak();
            echo esc_html__('Name:', 'seatreg'), ' ', esc_html($registration->first_name), ' ', esc_html($registration->last_name), $this->lineBreak();
            echo esc_html__('Email:', 'seatreg'), ' ', esc_html($registration->email), $this->lineBreak();
            echo esc_html__('Registration date:', 'seatreg'), ' ', $bookingDate, $this->lineBreak();
            echo esc_html__('Status:', 'seatreg'), ' ', $status, $this->lineBreak();

            if($status === "Approved") {
                $confirmDate = $this->getBookingDate($registration->booking_confirm_date);
 
                echo esc_html__('Confirmation date:', 'seatreg'), ' ', $confirmDate, $this->lineBreak();
            }
        
            foreach ($this->_customFields as $customField) {
                echo $this->customFieldsWithValues($customField, $registrantCustomData), $this->lineBreak();
            }
        
            echo $this->lineBreak(), $this->lineBreak();
        }
    }
}