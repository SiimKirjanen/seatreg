<?php

class SeatregImportService {
    private $seatregCode;
    private $registrationData;
    private $roomData;
    private $existingBookings;
    private $failedImports = array();
    private $successfulImports = array();
    private $importCount = 0;

    public function __construct($code) {
        $this->seatregCode = $code;
        $this->registrationData = SeatregRegistrationRepository::getRegistrationByCode($this->seatregCode);
        $this->roomData = json_decode($this->registrationData->registration_layout)->roomData;
        $this->existingBookings = SeatregBookingRepository::getAllConfirmedAndApprovedBookingsByRegistrationCode($this->seatregCode);
    }

    private function validateData($bookingData) {
        $validation = (object) [
            'isValid' => true,
            'messages' => array()
        ];
        
        $roomName = SeatregRegistrationService::getRoomNameFromLayout($this->roomData, $bookingData->room_uuid);

        if($roomName == null) {
            $obj->is_valid = false;
            $obj->messages[] = 'Invalid room UUID';

            return $validation;
        }

        $seatAndRoomValidation = SeatregLayoutService::validateRoomAndSeatId($this->roomData, $roomName, $bookingData->seat_id, $bookingData->seat_nr);
        if( !$seatAndRoomValidation->valid ) {
            $obj->is_valid = false;
            $obj->messages[] = $seatAndRoomValidation->errorText;
        }

        $seatBookedValidation = SeatregBookingService::checkIfSeatAlreadyBooked($bookingData->seat_id, $bookingData->seat_nr, $this->existingBookings);
        if( !$seatBookedValidation->is_valid ) {
            $obj->is_valid = false;
            $obj->messages = array_merge($obj->messages, $seatBookedValidation->messages);
        }

        return $validation;
    }

    private function insertData($bookingData) {
        return seatreg_add_booking(
            $bookingData->first_name,
            $bookingData->last_name,
            $bookingData->email,
            $bookingData->custom_field_data,
            $bookingData->seat_nr,
            $bookingData->seat_id,
            $bookingData->room_uuid,
            $this->seatregCode,
            $bookingData->status,
            $bookingData->booking_id,
            SeatregRandomGenerator::generateRandom($bookingData->email)
        );
    }

    public function importBookings($importedBookingsData) {

        $importedBookings = json_decode(stripslashes($importedBookingsData));

        $this->importCount = count($importedBookings);

        foreach( $importedBookings as $importedBooking ) {
            try {
                $validation = $this->validateData($importedBooking );

                if( $validation->isValid ) {
                    $inserted = $this->insertData($importedBooking);

                    if( $inserted ) {
                        $this->successfulImports[] = $importedBooking;
                    }
                }else {
                    $this->failedImports[] = $importedBooking;
                }
            }catch(Exception $e) {
                $this->failedImports[] = $importedBooking;
            }
        }

        $importFullySUccess = $this->importCount === count($this->successfulImports);

        return (object) [
            'success' => $importFullySUccess,
            'successfulImports' => $this->successfulImports,
            'failedImports' => $this->failedImports
        ];

    }
}