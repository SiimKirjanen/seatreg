<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_functions.php' );

class SeatregBookingsFile {
    protected $_registrationCode = null;
    protected $_showWhat = 'all';
    protected $_UTCDateTime = null;
    protected $_userTimezone = null;
    protected $_userDateTimeZone = null;
    protected $_currentDateTime = null;
    protected $_registrationInfo = null;
    protected $_registrations = null;
    protected $_registrationName = null;
    protected $_customFields = null;

    public function __construct($showPending, $showConfirmed, $timeZone, $registrationCode) {
        $this->_registrationCode = $registrationCode;
        $this->_userTimezone = $timeZone;

        if($showPending && !$showConfirmed) {
            $this->_showWhat = 'pending';
        }
        if($showConfirmed && !$showConfirmed) {
            $this->_showWhat = 'confirmed';
        }
        
        $this->setUp();
	}

    protected function setUp() {
        $this->_UTCDateTime = new DateTimeZone("UTC");

        try {
            $this->_userDateTimeZone = new DateTimeZone($this->_userTimezone);
        }catch(Exception $e) {
            wp_die(
                sprintf(
                    esc_html('Can\'t generate PDF because of Unknown or bad timezone (%s)'),
                    esc_html($this->_userTimezone)
                )
            );
        }

        $this->_currentDateTime = new DateTime(null, $this->_UTCDateTime);
        $this->_currentDateTime->setTimezone($this->_userDateTimeZone);
        $this->_registrationInfo = seatreg_get_options($this->_registrationCode)[0];
        $this->_registrations = seatreg_get_data_for_booking_file($this->_registrationCode, $this->_showWhat);
        $this->_registrationName = esc_html($this->_registrationInfo->registration_name);
        $this->_customFields = json_decode($this->_registrationInfo->custom_fields, true);
    }

    protected function customFieldsWithValues($label, $customData) {
        $cust_len = count(is_array($customData) ? $customData : []);
        $foundIt = false;
	    $string = $label . ': ';

        for($k = 0; $k < $cust_len; $k++) {
            if($customData[$k]['label'] == $label) {
                if($customData[$k]['value'] === true) {
                    $string .= esc_html__('Yes', 'seatreg');
                }else if($customData[$k]['value'] === false) {
                    $string .= esc_html__('No', 'seatreg');
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

    protected function getBookingDateTime($date) {
        $dateTime = new DateTime($date, $this->_UTCDateTime);
        $dateTime->setTimezone($this->_userDateTimeZone);

        return $dateTime;
    }
}