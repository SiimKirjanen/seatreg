<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/bookings/SeatregBookingsFile.php');

class SeatregBookingsTxt extends SeatregBookingsFile {

    public function __construct($showPending, $showConfirmed, $separateFirstandLastName, $registrationCode, $calendarDate) {
        parent::__construct($showPending, $showConfirmed, $separateFirstandLastName, $registrationCode, $calendarDate);

        $this->setupCsv();
	}

    private function setupCsv() {
        header("Content-type: text/csv");
        header('Content-Disposition: attachment; filename="'. esc_html($this->_registrationName) . ' '. $this->getFileName().'.csv"');
    }

    private function cleanJSONForCsv($json) {
        if ($json === null) {
            return '';
        }
        
        return $json;
    }
    
    public function printCsv() {
        $customFieldsLength = count($this->_customFields);
        $output = fopen('php://output', 'w');

        foreach ($this->_registrations as $registration) {
            $booking_id = sha1(mt_rand(10000,99999).time().$registration->booker_email);

            $csvRow = array(
                $registration->first_name,
                $registration->last_name,
                $registration->email,
                $registration->seat_id,
                $registration->seat_nr,
                $registration->room_uuid,
                $registration->booking_date,
                $registration->booking_confirm_date,
                $this->cleanJSONForCsv($registration->custom_field_data),
                $registration->status,
                $booking_id,
                $registration->booker_email,
                $this->cleanJSONForCsv($registration->multi_price_selection),
                $registration->logged_in_user_id,
            );

            fputcsv($output, $csvRow);
        }
        fclose($output);
        exit();
    }
}