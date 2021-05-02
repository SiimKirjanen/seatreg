<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

class SeatregValidationStatus {
    public $valid = true;
    public $errorMessage = '';

    public function setInvalid($message) {
        $this->valid = false;
        $this->errorMessage = $message;
    }

    public function getStatus() {
        return (object) [
            'valid' => $this->valid,
            'errorMessage' => $this->errorMessage,
        ];
    }
}

class SeatregDataValidation {
    public static function validateTabData($tabData) {
        $validationStatus = new SeatregValidationStatus();

        if( strlen($tabData) > SEATREG_REGISTRATION_NAME_MAX_LENGTH) {
            $validationStatus->setInvalid('Tab is too long');
        }

        return $validationStatus;
    }

    public static function tabsDataExists() {
        return !empty( $_GET[ 'tab' ] ) ;
    }

    public static function validateOrderData($order) {
        $validationStatus = new SeatregValidationStatus();

        if( !in_array($order, SEATREG_MANAGER_ALLOWED_ORDER )) {
            $validationStatus->setInvalid('Not supported order');
		}

        return $validationStatus;
    } 

    public static function orderDataExists() {
        return !empty( $_GET[ 'o' ] ) ;
    }

    public static function validateSearchData($searchTerm) {
        $validationStatus = new SeatregValidationStatus();

        if( strlen($searchTerm) > SEATREG_REGISTRATION_SEARCH_MAX_LENGTH ) {
			$validationStatus->setInvalid('Too long search');
		}

        return $validationStatus;
    }

    public static function searchDataExists() {
        return !empty( $_GET[ 's' ] ) ;
    }

    public static function validateRegistrationName($registrationName) {
        $validationStatus = new SeatregValidationStatus();

        if( strlen($registrationName) > SEATREG_REGISTRATION_NAME_MAX_LENGTH ) {
			$validationStatus->setInvalid('Registration name too long');
		}

        return $validationStatus;
    }

    public static function registrationNameDataExists($method) {
        return !empty( $method[ 'registration-name' ] );
    }

    public static function registrationCodeDataExists($method) {
        return !empty( $method[ 'registration_code' ] );
    }
}