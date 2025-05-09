<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

function SeatregFindCustomField($customFieldLabel, $createdCustomFields) {
    foreach($createdCustomFields as $createdCustomField) {
        if(trim($createdCustomField->label) === trim($customFieldLabel)) {
            return $createdCustomField;
        }
    }

    return false;
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

    public static function updateLayoutDataExists() {
        return !empty($_POST['updatedata']);
    }

    public static function calendarDatesValdiation($method) {
        if( !empty($_POST['calendar-dates']) && !preg_match('/^([0-9]{4}-[0-9]{1,2}-[0-9]{1,2},?)+$/', $_POST['calendar-dates']) ) {
            return false;
        }

        return true;
    }

    public static function layoutDataIsCorrect($data) {
        $validationStatus = new SeatregValidationStatus();
        $layout = json_decode($data);

        if( !property_exists($layout, 'global') ) {
            $validationStatus->setInvalid('global property is missing');
            return $validationStatus;
        }

        if( !property_exists($layout->global, 'roomLocator') || !is_int($layout->global->roomLocator) ) {
            $validationStatus->setInvalid('roomLocator property is missing or invalid');
            return $validationStatus;
        }

        if( !property_exists($layout->global, 'boxCounter') || !is_int($layout->global->boxCounter) ) {
            $validationStatus->setInvalid('boxCounter property is missing or invalid');
            return $validationStatus;
        }

        if( !property_exists($layout, 'roomData') || !is_array($layout->roomData) ) {
            $validationStatus->setInvalid('roomData property is missing or invalid');
            return $validationStatus;
        }

        if( property_exists($layout, 'legends') && !is_array($layout->legends) ) {
            $validationStatus->setInvalid('roomData property legends invalid');
            return $validationStatus;
        }

        if( property_exists($layout, 'legends') ) {
            foreach($layout->legends as $legend) {
                if( !is_object($legend) ) {
                    $validationStatus->setInvalid('legend is invalid');
                    return $validationStatus;
                }

                if( !property_exists($legend, 'text') || !is_string($legend->text) ) {
                    $validationStatus->setInvalid('legend text is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($legend, 'color') || !is_string($legend->color) ) {
                    $validationStatus->setInvalid('legend color is missing or invalid');
                    return $validationStatus;
                }
            }
        }

        foreach($layout->roomData as $roomData) {
            if( !property_exists($roomData, 'skeleton') ) {
                $validationStatus->setInvalid('skeleton missing in room');
                return $validationStatus;
            }

            if( !property_exists($roomData->skeleton, 'width') || !is_int($roomData->skeleton->width) ) {
                $validationStatus->setInvalid('skeleton width missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->skeleton, 'height') || !is_int($roomData->skeleton->height) ) {
                $validationStatus->setInvalid('skeleton height missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->skeleton, 'countX') || !is_int($roomData->skeleton->countX) ) {
                $validationStatus->setInvalid('skeleton countX missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->skeleton, 'countY') || !is_int($roomData->skeleton->countY) ) {
                $validationStatus->setInvalid('skeleton countY missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->skeleton, 'marginX') || !is_int($roomData->skeleton->marginX) ) {
                $validationStatus->setInvalid('skeleton marginX missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->skeleton, 'marginY') || !is_int($roomData->skeleton->marginY) ) {
                $validationStatus->setInvalid('skeleton marginY missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->skeleton, 'buildGrid') || !is_int($roomData->skeleton->buildGrid) ) {
                $validationStatus->setInvalid('skeleton buildGrid missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData, 'room') ) {
                $validationStatus->setInvalid('room missing in room');
                return $validationStatus;
            }

            if( !property_exists($roomData->room, 'id') || !is_int($roomData->room->id) ) {
                $validationStatus->setInvalid('room id missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->room, 'uuid') || !is_string($roomData->room->uuid) ) {
                $validationStatus->setInvalid('room uuid missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->room, 'name') || !is_string($roomData->room->name) ) {
                $validationStatus->setInvalid('room name is missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->room, 'text') || !is_string($roomData->room->text) ) {
                $validationStatus->setInvalid('room text missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->room, 'legends') || !is_array($roomData->room->legends) ) {
                $validationStatus->setInvalid('room legends missing or invalid');
                return $validationStatus;
            }

            if( property_exists($roomData->room, 'legends') ) {
                foreach($roomData->room->legends as $legend) {
                    if( !is_object($legend) ) {
                        $validationStatus->setInvalid('legend is invalid');
                        return $validationStatus;
                    }
    
                    if( !property_exists($legend, 'text') || !is_string($legend->text) ) {
                        $validationStatus->setInvalid('legend text is missing or invalid');
                        return $validationStatus;
                    }
    
                    if( !property_exists($legend, 'color') || !is_string($legend->color) ) {
                        $validationStatus->setInvalid('legend color is missing or invalid');
                        return $validationStatus;
                    }
                }
            }

            if( !property_exists($roomData->room, 'width') || !is_numeric($roomData->room->width) ) {
                $validationStatus->setInvalid('room width missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->room, 'height') || !is_numeric($roomData->room->height) ) {
                $validationStatus->setInvalid('room height missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->room, 'seatCounter') || !is_int($roomData->room->seatCounter) ) {
                $validationStatus->setInvalid('room seatCounter missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData->room, 'backgroundImage') || !( is_string($roomData->room->backgroundImage) || is_null($roomData->room->backgroundImage) ) ) {
                $validationStatus->setInvalid('room backgroundImage missing or invalid');
                return $validationStatus;
            }

            if( !property_exists($roomData, 'boxes') || !is_array($roomData->boxes) ) {
                $validationStatus->setInvalid('room missing boxes');
                return $validationStatus;
            }

            foreach($roomData->boxes as $box) {
                if( !is_object($box) ) {
                    $validationStatus->setInvalid('roomData box is invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'legend') || !is_string($box->legend) ) {
                    $validationStatus->setInvalid('box legend is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'xPosition') || !is_int($box->xPosition) ) {
                    $validationStatus->setInvalid('box xPosition is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'yPosition') || !is_int($box->yPosition) ) {
                    $validationStatus->setInvalid('box yPosition is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'width') || !is_int($box->width) ) {
                    $validationStatus->setInvalid('box width is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'height') || !is_int($box->height) ) {
                    $validationStatus->setInvalid('box height is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'color') || !is_string($box->color) ) {
                    $validationStatus->setInvalid('box color is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'hoverText') || !is_string($box->hoverText) ) {
                    $validationStatus->setInvalid('box hoverText is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'id') || !is_string($box->id) ) {
                    $validationStatus->setInvalid('box id is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'canRegister') || !is_string($box->canRegister) ) {
                    $validationStatus->setInvalid('box canRegister is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'seat') ) {
                    $validationStatus->setInvalid('box seat is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'status') || !is_string($box->status) ) {
                    $validationStatus->setInvalid('box status is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'zIndex') || !is_int($box->zIndex) ) {
                    $validationStatus->setInvalid('box zIndex is missing or invalid');
                    return $validationStatus;
                }

                if( !property_exists($box, 'price') || (is_int($box->price) && $box->price < 0) ) {
                    $validationStatus->setInvalid('box price is missing or invalid');
                    return $validationStatus;
                }

                if( property_exists($box, 'price') && is_array($box->price) ) {
                    foreach($box->price as $price) {
                        if( !is_object($price) ) {
                            $validationStatus->setInvalid('Price is invalid (multi price)');
                            return $validationStatus;
                        }
        
                        if( !property_exists($price, 'price') ) {
                            $validationStatus->setInvalid('Price is missing (multi price)');
                            return $validationStatus;
                        }
        
                        if( !property_exists($price, 'description') || !is_string($price->description) || strlen($price->description) === 0) {
                            $validationStatus->setInvalid('Price description is missing or invalid (multi price)');
                            return $validationStatus;
                        }
                    }
                }

                if( !property_exists($box, 'lock') || !is_bool($box->lock) ) {
                    $validationStatus->setInvalid('box lock is missing or invalid');
                    return $validationStatus;
                }
            }
        }

        return $validationStatus;
    }

    public static function validateCustomPaymentCreation($customPayments) {
        $validationStatus = new SeatregValidationStatus();

        try {
            $customPaymentsDecoded = json_decode($customPayments);

            if( !is_array($customPaymentsDecoded) ) {
                $validationStatus->setInvalid('Custom payments not array');

                return $validationStatus;
            }

            foreach($customPaymentsDecoded as $customPaymentDecoded) {
                if( !property_exists($customPaymentDecoded, 'title') || !is_string($customPaymentDecoded->title) || !preg_match('/^[\p{L}\p{N}+\s]+$/u', $customPaymentDecoded->title) ) {
                    $validationStatus->setInvalid('Custom payment title is missing or invalid');

                    return $validationStatus;
                }

                if( !property_exists($customPaymentDecoded, 'description') || !is_string($customPaymentDecoded->description) || !preg_match(SEATREG_CUSTOM_PAYMENT_DESCRIPTION, $customPaymentDecoded->description) ) {
                    $validationStatus->setInvalid('Custom payment description is missing or invalid');

                    return $validationStatus;
                }
            }


        }catch(Exception $error) {
            $validationStatus->setInvalid('Unexpected error occured while validating custom payments');
        }

        return $validationStatus;
    }

    public static function validateCustomFieldCreation($customFields) {
        $validationStatus = new SeatregValidationStatus();

        try {
            $customFieldsDecoded = json_decode($customFields);

            if( !is_array($customFieldsDecoded) ) {
                $validationStatus->setInvalid('Custom fields not array');
                return $validationStatus;
            }

            foreach($customFieldsDecoded as $customFieldDecoded) {
                $duplicates = array_filter($customFieldsDecoded, function($cust) use ($customFieldDecoded) {
                    return $cust->label === $customFieldDecoded->label;
                });

                if( count($duplicates) > 1) {
                    $validationStatus->setInvalid('Duplicate label detected');
                    return $validationStatus;
                }

                if( !property_exists($customFieldDecoded, 'label') || !is_string($customFieldDecoded->label) || !preg_match('/^[\p{L}\p{N}+\s]+$/u', $customFieldDecoded->label) ) {
                    $validationStatus->setInvalid('Custom field label is missing or invalid');
                    return $validationStatus;
                }
                if( !property_exists($customFieldDecoded, 'type') || !in_array($customFieldDecoded->type, SEATREG_CUSTOM_FIELD_TYPES) ) {
                    $validationStatus->setInvalid('Custom field type missing or invalid');
                    return $validationStatus;
                }
                if( !property_exists($customFieldDecoded, 'options') || !is_array($customFieldDecoded->options) ) {
                    $validationStatus->setInvalid('Custom fields options missing or not an array');
                    return $validationStatus;
                }
                foreach($customFieldDecoded->options as $option) {
                    if( !is_string($option) ) {
                        $validationStatus->setInvalid('Custom fields option not a string');
                        return $validationStatus;
                    }
                }
            }

        } catch (Exception $error) {
            $validationStatus->setInvalid('Unexpected error occured');
        }
        
        return $validationStatus;
    }

    public static function validateCustomFieldManagerSubmit($editCustomFields, $existingCustomFields, $registrationCode) {
        $validationStatus = new SeatregValidationStatus();

        try {
            $editCustomFieldsDecoded = json_decode($editCustomFields);
            $existingCustomFieldsDecoded = json_decode($existingCustomFields);

            foreach($editCustomFieldsDecoded as $editCustomFieldDecoded) {
                $customFieldValidation = self::validateSingleCustomFieldSubmit($editCustomFieldDecoded, $editCustomFieldsDecoded, $existingCustomFieldsDecoded, $registrationCode);

                if( !$customFieldValidation->valid ) {
                    $validationStatus->setInvalid($personCustomFieldValidation->errorMessage);
                    return $validationStatus;
                }
            }

        }catch(Exception $error) {
            $validationStatus->setInvalid('Unexpected error occured');
        }

        return $validationStatus;
    }

    public static function validateBookingCustomFields($submittedCustomFields, $maxSeats, $createdCustomFields, $registrationCode) {
        $validationStatus = new SeatregValidationStatus();

        try {
            $customFieldsDecoded = json_decode($submittedCustomFields);

            if( !is_array($customFieldsDecoded) ) {
                $validationStatus->setInvalid('Custom fields not array');
                return $validationStatus;
            }

            if( count($customFieldsDecoded) > (int)$maxSeats ) {
                $validationStatus->setInvalid('Max seats limit exceeded');
                return $validationStatus;
            }

            foreach($customFieldsDecoded as $personCustomFields) {
                foreach($personCustomFields as $personCustomField) {
                    $personCustomFieldValidation = self::validateSingleCustomFieldSubmit($personCustomField, $personCustomFields,  $createdCustomFields, $registrationCode);

                    if( !$personCustomFieldValidation->valid ) {
                        $validationStatus->setInvalid($personCustomFieldValidation->errorMessage);
                        return $validationStatus;
                    }
                
                }
            }

        } catch(Exception $error) {
            $validationStatus->setInvalid('Unexpected error occured');
        }

        return $validationStatus;
    }

    public static function validateSingleCustomFieldSubmit($personCustomField, $personCustomFields, $createdCustomFields, $registrationCode) {
        $validationStatus = new SeatregValidationStatus();
        $assosiatedCustomField = SeatregFindCustomField($personCustomField->label, $createdCustomFields);

        if( !$assosiatedCustomField ) {
            $validationStatus->setInvalid('Entered custom field was not found');
            return $validationStatus;
        }
        $isOptional = $assosiatedCustomField->optional ?? false;
        $customFieldValue = trim($personCustomField->value);

        if( !property_exists($personCustomField, 'label') || !is_string($personCustomField->label) ) {
            $validationStatus->setInvalid('Custom field label is missing or invalid');
            return $validationStatus;
        }
     
        if( !property_exists($personCustomField, 'value') ) {
            $validationStatus->setInvalid('Custom field value is missing');
            return $validationStatus;
        }

        if (!$isOptional && $customFieldValue === '') {
            $validationStatus->setInvalid('Custom field value is required');
            return $validationStatus;
        }

        if( $customFieldValue !== '' && !preg_match('/^[\p{L}\p{N}\\s:\/.,-:;?]+$/u', $customFieldValue) ) {
            $validationStatus->setInvalid('Custom field value is invalid');
            return $validationStatus;
        }

        $duplicates = array_filter($personCustomFields, function($cust) use ($personCustomField) {
            return $cust->label === $personCustomField->label;
        });

        if( count($duplicates) > 1) {
            $validationStatus->setInvalid('Duplicate label detected');
            return $validationStatus;
        }

        if($assosiatedCustomField->type === 'check') {
            if( !in_array($personCustomField->value, array('0', '1')) ) {
                $validationStatus->setInvalid('Checkbox value is invalid');
                return $validationStatus;
            }
        }

        if($assosiatedCustomField->type === 'text') {
            if( strlen($personCustomField->value) > SEATREG_CUSTOM_TEXT_FIELD_MAX_LENGTH ) {
                $validationStatus->setInvalid('Text field too long');
                return $validationStatus;
            }
            if( $assosiatedCustomField->unique === true ) {
                $foundExistingUnique = SeatregBookingRepository::findIfExistingBookingWasMadeWithCustomFieldValue($registrationCode, $assosiatedCustomField, $personCustomField);

                if($foundExistingUnique) {
                    $validationStatus->setInvalid(sprintf(esc_html__('%s field value is already used', 'seatreg'), $assosiatedCustomField->label));
                    return $validationStatus;
                }
            }
        }

        if($assosiatedCustomField->type === 'sel') {
            if( !in_array($personCustomField->value, $assosiatedCustomField->options) ) {
                $validationStatus->setInvalid('Select option does not exist');
                return $validationStatus;
            }
        }

        return $validationStatus;
    }

    public static function validateDefaultInputOnBookingSubmit($firstName, $lastname, $email, $requireName = true) {
        $validationStatus = new SeatregValidationStatus();

        if(strlen($firstName) > SEATREG_DEFAULT_INPUT_MAX_LENGHT || strlen($lastname) > SEATREG_DEFAULT_INPUT_MAX_LENGHT || strlen($email) > SEATREG_DEFAULT_INPUT_MAX_LENGHT ) {
            $validationStatus->setInvalid('Default input too long');
            return $validationStatus;
        }

        if ( $requireName ) {
            if(!preg_match('/^[\p{L}\p{N}\\s-]*$/u', $firstName) || !preg_match('/^[\p{L}\p{N}\\s-]*$/u', $lastname)) {
                $validationStatus->setInvalid('Illegal characters in default inputs');
                return $validationStatus;
            }
        }else {
            if ($firstName !== '' || $lastname !== '') {
                $validationStatus->setInvalid('First and last name must be empty when name is not required');
                return $validationStatus;
            }
        }

        if(!is_email($email)) {
            $validationStatus->setInvalid('Email is not correct');
            return $validationStatus;
        }

        return $validationStatus;
    }

    public static function validateBookingData($seatId, $seatNr, $roomUUID) {
        $validationStatus = new SeatregValidationStatus();

        if(!preg_match('/^[\p{L}\p{N}]+$/u', $seatId)) {
            $validationStatus->setInvalid('Illegal characters in booking data');
            return $validationStatus;
        }

        if(!preg_match('/^[\p{L}\p{N}-]+$/u', $roomUUID)) {
            $validationStatus->setInvalid('Illegal characters in booking data');
            return $validationStatus;
        }

        return $validationStatus;
    }

    public static function validateEmailVerificationTemplate() {
        if( !$_POST['email-verification-template'] ) {
            return true;
        }else {
            return strpos($_POST['email-verification-template'], SEATREG_TEMPLATE_EMAIL_VERIFICATION_LINK) !== false;
        }
    }

    public static function validatePendingBookingEmailTemplate() {
        if( !$_POST['pendin-booking-email-template'] ) {
            return true;
        }else {
            return strpos($_POST['pendin-booking-email-template'], SEATREG_TEMPLATE_STATUS_LINK) !== false;
        }
    }

    public static function validateApprovedBookingEmailTemplate() {
        if( !$_POST['approved-booking-email-template'] ) {
            return true;
        }else {
            return strpos($_POST['approved-booking-email-template'], SEATREG_TEMPLATE_STATUS_LINK) !== false;
        }
    }

    public static function validateEmailAddress($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateCurrencyCode($currencyCode) {
        return in_array(strtoupper($currencyCode), SEATREG_VALID_CURRENCY_CODES);
    }

    public static function validateRegistrationCode($registrationCode) {
        $pattern = '/^[a-zA-Z0-9]{10}$/';

        return preg_match($pattern, $registrationCode);
    }

    public static function validateNumberic($number) {
        return preg_match('/^[0-9]+$/', $number);
    }
}
