<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/bookings/SeatregBookingsFile.php');

class SeatregBookingsCSV extends SeatregBookingsFile {

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
            $csvRow = array_fill(0, 14, '');
            $usedCouponString = SeatregCouponService::getAppliedCouponString(json_decode($registration->applied_coupon) ?? null);

            $csvRow[SEATREG_CSV_COL_FIRST_NAME] = $registration->first_name;
            $csvRow[SEATREG_CSV_COL_LAST_NAME] = $registration->last_name;
            $csvRow[SEATREG_CSV_COL_EMAIL] = $registration->email;
            $csvRow[SEATREG_CSV_COL_SEAT_ID] = $registration->seat_id;
            $csvRow[SEATREG_CSV_COL_SEAT_NR] = $registration->seat_nr;
            $csvRow[SEATREG_CSV_COL_ROOM_UUID] = $registration->room_uuid;
            $csvRow[SEATREG_CSV_COL_BOOKING_DATE] = $registration->booking_date;
            $csvRow[SEATREG_CSV_COL_BOOKING_CONFIRM_DATE] = $registration->booking_confirm_date;
            $csvRow[SEATREG_CSV_COL_CUSTOM_FIELD_DATA] = $this->cleanJSONForCsv($registration->custom_field_data);
            $csvRow[SEATREG_CSV_COL_STATUS] = $registration->status;
            $csvRow[SEATREG_CSV_COL_BOOKING_ID] = $booking_id;
            $csvRow[SEATREG_CSV_COL_BOOKER_EMAIL] = $registration->booker_email;
            $csvRow[SEATREG_CSV_COL_MULTI_PRICE_SELECTION] = $this->cleanJSONForCsv($registration->multi_price_selection);
            $csvRow[SEATREG_CSV_COL_LOGGED_IN_USER_ID] = $registration->logged_in_user_id;
            $csvRow[SEATREG_CSV_COL_USED_COUPON] = $usedCouponString;

            fputcsv($output, $csvRow);
        }
        fclose($output);
        exit();
    }
}