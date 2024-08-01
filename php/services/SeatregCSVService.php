<?php

class ValidationResult {
    public $isValid;
    public $message;
    public $data;

    public function __construct($isValid, $message) {
        $this->isValid = $isValid;
        $this->message = $message;
    }
}

class SeatregCSVService {
    private $seatregCode;
    private $registrationData;
    private $roomData;
    private $existingBookings;

    public function __construct($code) {
        $this->seatregCode = $code;
        $this->registrationData = SeatregRegistrationRepository::getRegistrationByCode($this->seatregCode);
        $this->roomData = json_decode($this->registrationData->registration_layout)->roomData;
        $this->existingBookings = SeatregBookingRepository::getAllConfirmedAndApprovedBookingsByRegistrationCode($this->seatregCode);
    }

    public function validateCSV($file) {
        $file_extension = $this->getExtension($file);
        if ($file_extension != 'csv') {
            return new ValidationResult(false, 'Invalid file extension. Please upload a CSV file generated at the booking manager.');
        }

        $mime_type = $this->getMimeType($file);
        if ($mime_type !== 'text/csv') {
            return new ValidationResult(false, "Invalid mime type $mime_type. Please upload a CSV file generated at the booking manager.");
        } 

        // Validate that each row has the correct number of columns
        $file_handle = fopen($file['tmp_name'], 'r');
        $expectedColumnCount = 14;

        while (($row = fgetcsv($file_handle)) !== false) {
            $rowCount = count($row);
            if ($rowCount  != $expectedColumnCount) {
                fclose($file_handle);
                return new ValidationResult(false, 'Each row must contain exactly ' . $expectedColumnCount . ' columns. But got ' . $rowCount . ' columns. ' . json_encode($row));
            }
        }

        fclose($file_handle);

        return new ValidationResult(true, 'ok');
    }

    public function validateData($file) {
        $validatedData = array();
        $file_handle = fopen($file['tmp_name'], 'r');

        while (($row = fgetcsv($file_handle)) !== false) {
            $obj = (object) ['csv_row' => $row, 'is_valid' => true, 'messages' => array()];

            $roomName = SeatregRegistrationService::getRoomNameFromLayout($this->roomData, $row[SEATREG_CSV_COL_ROOM_UUID]);
            if($roomName == null) {
                $obj->is_valid = false;
                $obj->messages[] = 'Invalid room UUID';
            }

            $seatAndRoomValidation = SeatregLayoutService::validateRoomAndSeatId($this->roomData, $roomName, $row[SEATREG_CSV_COL_SEAT_ID], $row[SEATREG_CSV_COL_SEAT_NR]);
            if( !$seatAndRoomValidation->valid ) {
                $obj->is_valid = false;
                $obj->messages[] = $seatAndRoomValidation->errorText;
            }

            $seatBookedValidation = SeatregBookingService::checkIfSeatAlreadyBooked($row, $this->existingBookings);

            if( !$seatBookedValidation->is_valid ) {
                $obj->is_valid = false;
                $obj->messages = array_merge($obj->messages, $seatBookedValidation->messages);
            }

            $validatedData[] = $obj;
        }

        fclose($file_handle);

        return $validatedData;
    }
    
    public function getExtension($file) {
        return pathinfo($file['name'], PATHINFO_EXTENSION);
    }

    public function getMimeType($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        return $mime_type;
    }
}