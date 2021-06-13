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

    public function __construct($showPending, $showConfirmed, $registrationCode) {
        $this->_registrationCode = $registrationCode;
        $this->_currentTimestamp = time();

        if($showPending && !$showConfirmed) {
            $this->_showWhat = 'pending';
        }
        if($showConfirmed && !$showPending) {
            $this->_showWhat = 'confirmed';
        }
        
        $this->setUp();
	}

    protected function setUp() {
        $this->_registrationInfo = seatreg_get_options($this->_registrationCode)[0];
        $this->_registrations = seatreg_get_data_for_booking_file($this->_registrationCode, $this->_showWhat);
        $this->_registrationName = esc_html($this->_registrationInfo->registration_name);
        $this->_customFields = ($this->_registrationInfo->custom_fields !== null) ? json_decode($this->_registrationInfo->custom_fields, true) : [];
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
}