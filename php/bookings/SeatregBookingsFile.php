<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_functions.php' );

class SeatregBookingsFile {
    protected $_registrationCode = null;
    protected $_showWhat = 'all';
    protected $_registrationInfo = null;
    protected $_registrations = null;
    protected $_registrationName = null;
    protected $_customFields = null;
    protected $_currentTimestamp = null;
    protected $_usingSeats = true;
    protected $_calendarDate = null;

    public function __construct($showPending, $showConfirmed, $registrationCode, $calendarDate) {
        $this->_registrationCode = $registrationCode;
        $this->_currentTimestamp = time();

        if( $showPending && !$showConfirmed ) {
            $this->_showWhat = 'pending';
        }
        if( $showConfirmed && !$showPending ) {
            $this->_showWhat = 'confirmed';
        }
        if( $calendarDate ) {
            $this->_calendarDate = $calendarDate;
        }
        
        $this->setUp();
	}

    protected function setUp() {
        $this->_registrationInfo = seatreg_get_options($this->_registrationCode)[0];
        $this->_customFields = ($this->_registrationInfo->custom_fields !== null) ? json_decode($this->_registrationInfo->custom_fields, true) : [];
        $this->_registrations = $this->filtering( seatreg_get_data_for_booking_file($this->_registrationCode, $this->_showWhat, $this->_calendarDate) );
        $this->_registrationName = esc_html($this->_registrationInfo->registration_name);
        $this->_usingSeats = $this->_registrationInfo->using_seats === '1';
    }

    protected function customFieldsWithValues($customField, $customData) {
        $cust_len = count(is_array($customData) ? $customData : []);
        $foundIt = false;
	    $string = $customField['label'] . ': ';

        for($k = 0; $k < $cust_len; $k++) {
            if($customData[$k]['label'] == $customField['label'] ) {

                if($customField['type'] === 'check') {
                    if($customData[$k]['value'] === '1') {
                        $string .= esc_html__('Checked', 'seatreg');
                    }else if($customData[$k]['value'] === '0') {
                        $string .= esc_html__('Unchecked', 'seatreg');
                    }
                }else {
                    $string .= esc_html($customData[$k]['value']);
                }
                $foundIt = true;
    
                break;
            }
        }

        if(!$foundIt) {
            $string .= esc_html__(' not set', 'seatreg');
        }
    
        return $string;
    }

    protected function getStatus($status) {
        return $status === "2" ? "Approved" : "Pending";
    }

    protected function getBookingDate($timestamp) {
        return date('M j Y h:i e', $timestamp);
    }

    protected function getFileName() {
        return date('Y-M-d', $this->_currentTimestamp);
    }

    private function filtering( $bookingsData ) {
        return array_filter($bookingsData, function($booking) {
            $bookingCustomFieldData = json_decode($booking->custom_field_data);

            if( isset( $_GET['name'] ) ) {
                if( !str_contains( $booking->first_name . $booking->last_name, $_GET['name'] ) ) {
                    return false;
                }
            }

            if( isset( $_GET['email'] ) ) {
                if( $booking->email !== $_GET['email'] ) {
                    return false;
                }
            }

            foreach( $this->_customFields as $customField ) {
                if( isset( $_GET[$customField["label"]] ) ) {
                    $data = array_values(array_filter($bookingCustomFieldData, function($customFieldData) use ($customField)  {
                        return $customFieldData->label === $customField["label"];
                    }));
                
                    if( !$data || count($data) === 0) {
                        return false;
                    }

                    if( $data[0]->value !== $_GET[$customField["label"]] ) {
                        return false;
                    }
                }
            }

            return true;
        });
    }
}