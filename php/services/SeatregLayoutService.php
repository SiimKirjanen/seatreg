<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregLayoutService {
    public static function findBox($layout, $boxId) {
        $targetBox = null;
        
        foreach( $layout->roomData as $roomData ) {
            foreach( $roomData->boxes as $box ) {
                if( $box->id === $boxId ) {
                    $targetBox = $box;
                    break 2;
                }
            }
       }

       return $box;
    }

    public static function checkIfSeatLocked($layout, $boxId) {
        $box = self::findBox($layout, $boxId);

        if($box) {
            if($box->lock === true) {
                return true;
            }else {
                return false;
            }
        }
    }

    public static function checkIfSeatHasPassword($layout, $boxId) {
        $box = self::findBox($layout, $boxId);

        if($box) {
            if($box->password === "") {
                return false;
            }else {
                return true;
            }
        }
    }

    public static function getSeatPassword($layout, $boxId) {
        $box = self::findBox($layout, $boxId);

        if($box) {
            return $box->password;
        }
    }

    public static function hideSensitiveData($layout) {   
       if(!$layout) {
            return $layout;
       } 

       $layout = json_decode($layout); 

       foreach( $layout->roomData as $roomData ) {
            foreach( $roomData->boxes as $box ) {
                if( property_exists($box, 'password') && $box->password ) {
                    $box->password = true;
                }else {
                    $box->password = false;
                }
            }
       }
       
       return json_encode($layout);
    }

    public static function validateRoomAndSeatId($layout, $bookingRoomName, $bookingSeatId, $seatNr = null, $roomNouns = null) {
        $status = (object) [
            'valid' => false,
            'searchStatus' => '',
            'errorText' => ''
        ];
        $roomNoun = $roomNouns ? $roomNouns->singularUpper : SeatregTerminologyService::getRoomNouns()->singularUpper;

        foreach( $layout as $layoutData ) {
            $room = $layoutData->room;
            $status->searchStatus = 'room-searching';
            /* translators: %1$s: the word the admin uses for a room, capitalized, %2$s: Room name */
            $status->errorText = sprintf( esc_html__('%1$s %2$s does not exist!', 'seatreg'), esc_html($roomNoun), esc_html($bookingRoomName) );
    
            if( $room->name == $bookingRoomName ) {
                $status->searchStatus = 'seat-id-searching';

                if ($seatNr !== null) {
                    /* translators: %1$s: Seat ID, %2$s: Seat number, %3$s: Room name */
                    $status->errorText = sprintf(esc_html__('Seat id %1$s with number %2$s does not exist in %3$s', 'seatreg'), esc_html($bookingSeatId), esc_html($seatNr), esc_html($bookingRoomName));
                } else {
                    /* translators: %1$s: Seat ID, %2$s: Room name */
                    $status->errorText = sprintf(esc_html__('Seat id %1$s does not exist in %2$s', 'seatreg'), esc_html($bookingSeatId), esc_html($bookingRoomName));
                }
                
                foreach( $layoutData->boxes as $box ) {    
                    if( $box->canRegister == 'true' && $box->id == $bookingSeatId && ( $seatNr === null || $box->seat == $seatNr ) ) {
                        $status->errorText = '';
                        $status->valid = true;

                        break 2;
                    }
                }

                break;
            }
        }

        return $status;
    }

    public static function getBoxFromLayout($layout, $bookingSeatId) {
        $targetBox = null;

        foreach( $layout as $layoutData ) {
            $room = $layoutData->room;

            foreach( $layoutData->boxes as $box ) {    
                if( $box->id == $bookingSeatId ) {
                    $targetBox = $box;

                    break 2;
                }
            }
        }

        return $targetBox;
    }

    public static function getRoomUUID($layout, $roomName) {
        $roomUUID = null;

        foreach( $layout as $layoutData ) {
            $room = $layoutData->room;

            if($room->name === $roomName) {
                $roomUUID = $room->uuid;

                break;
            }

        }

        return $roomUUID;
    }

     /**
     *
     * Find room UUID by room name. Match ignores case and surrounding spaces, like the room name uniqueness check in the map builder
     * @param array $roomData Rooms in the layout
     * @param string $roomName Name of the room to look for
     * @return string|null Room UUID. Null when no room has that name
     *
    */
    public static function findRoomUuidByName($roomData, $roomName) {
        foreach( $roomData as $layoutData ) {
            if( strcasecmp( trim($layoutData->room->name), trim($roomName) ) === 0 ) {
                return $layoutData->room->uuid;
            }
        }

        return null;
    }

    public static function getRoomsLength($roomData) {
        return count($roomData);
    }

     /**
     *
     * Return room data from registration layout
     * @param string|null $registrationLayout Registration layout JSON
     * @return array Rooms in the layout. Empty array when the layout is missing or empty
     *
    */
    public static function getRoomDataFromLayout($registrationLayout) {
        $layout = $registrationLayout ? json_decode($registrationLayout) : null;

        return isset($layout->roomData) ? $layout->roomData : array();
    }

     /**
     *
     * check if multi price UUID exists 
     * @param object $booking Booking
     * @param array $layout Registration layout
     * @return string|false Returns false if the price UUID won't exist on the seat. If exist return the multi-price object
     * 
    */

    public static function checkIfMultiPriceUUIDExists($booking, $layout) {
        $multiPriceUUIDFound = false;

        foreach( $layout as $layoutData ) {
            if( $layoutData->room->uuid === $booking->room_uuid ) {
                foreach( $layoutData->boxes as $box ) {
                    if( $box->id === $booking->seat_id ) {
                        $prices = $box->price;

                        foreach( $prices as $price ) {
                            if( $price->uuid === $booking->multi_price_selection ) {
                                $multiPriceUUIDFound = $price;

                                break 3;
                            }
                        }   
                    }
                }
                break;
            }
        }

        return $multiPriceUUIDFound;
    }

     /**
     *
     * Return seat price from registration layout
     *
    */
    public static function getSeatPriceFromLayout($booking, $roomsData) {
        $priceObject = (object)array(
            'price' => null,
            'description' => null,
            'uuid' => null
        );

        foreach($roomsData as $roomData) {
            if($roomData->room->uuid === $booking->room_uuid) {
                foreach($roomData->boxes as $box) {
                    if($box->id === $booking->seat_id) {
                        if( $booking->multi_price_selection ) {
                            //multy price selection
                            foreach($box->price as $multyPrice) {
                                if($multyPrice->uuid === $booking->multi_price_selection) {
                                    $priceObject->price = $multyPrice->price;
                                    $priceObject->description = $multyPrice->description;
                                    $priceObject->uuid = $multyPrice->uuid;
                                }
                            } 
                        }else {
                            $priceObject->price = $box->price;
                        }
                        
                        break 2;
                    }
                }
            }
        }

	    return $priceObject;
    }

    public static function getBookingsInfoForLayout($structure, $code, $filterCalendarDate) {
        $pendingBookingsRoomsInfo = SeatregBookingRepository::getRoomsBookingsInfo($code, 1, $filterCalendarDate);
	    $confirmedBookingsRoomsInfo = SeatregBookingRepository::getRoomsBookingsInfo($code, 2, $filterCalendarDate);

	    $statsArray = self::getSeatsStats($structure, $pendingBookingsRoomsInfo, $confirmedBookingsRoomsInfo);

	    return $statsArray;	
    }

    public static function getSeatsStats($struct, $bronRegistrations, $takenRegistrations) {
        $registration = $struct ? json_decode($struct) : null;
        if(!isset($registration->roomData)) {
            return [];
        }

        $bronLength = count($bronRegistrations);
        $takenLength = count($takenRegistrations);
        $regStructure = $registration->roomData;
        $roomCount = count(is_array($regStructure) ? $regStructure : []);
        $howManyRegSeats = 0;
        $howManyOpenSeats = 0;
        $howManyBronSeats= 0;
        $howManyTakenSeats = 0;
        $howManyCustomBoxes = 0;
        $statsArray = array();
        $roomsInfo = array();

        for($i = 0; $i < $roomCount; $i++) {
            $roomBoxes = $regStructure[$i]->boxes;
            //find how many bron seats in this room
            $roomBoxCount = count($roomBoxes);
            $roomRegSeats = 0;  //how many reg seats
            $roomOpenSeats = 0; //how many open reg seats
            $roomTakenSeats = 0; //how many taken seats
            $roomBronSeats = 0;	//bron seats
            $roomCustomBoxes = 0;
    
            for($k = 0; $k < $bronLength; $k++) {  
                if( $regStructure[$i]->room->uuid == $bronRegistrations[$k]->room_uuid ) { //find how many bron seats in this room
                    $roomBronSeats = $bronRegistrations[$k]->total;
                    $howManyBronSeats += $bronRegistrations[$k]->total;
    
                    break;
                }
            }
    
            for($k = 0; $k < $takenLength; $k++) {
                if($regStructure[$i]->room->uuid == $takenRegistrations[$k]->room_uuid) { //find how many taken seats in this room
                    $roomTakenSeats = $takenRegistrations[$k]->total;
                    $howManyTakenSeats += $takenRegistrations[$k]->total;
    
                    break;
                }
            }
            
            for($j = 0; $j < $roomBoxCount; $j++) {
                if($roomBoxes[$j]->canRegister === 'true') {
                    if($roomBoxes[$j]->status === 'noStatus') {
                        $howManyOpenSeats++;
                        $roomOpenSeats++;
                    }
    
                    $howManyRegSeats++;
                    $roomRegSeats++;
                }else {
                    $howManyCustomBoxes++;
                    $roomCustomBoxes++;
                }
            }
    
            //roomOpenSeats counts from every bookable seat, roomOpenNoStatusSeats only from the ones left
            //without a status - the basis the registration wide openSeats below is counted on
            $roomsInfo[] = array(
                'roomUuid' => $regStructure[$i]->room->uuid,
                'roomName' => $regStructure[$i]->room->name,
                'roomSeatsTotal' => $roomRegSeats,
                'roomOpenSeats' => $roomRegSeats - $roomTakenSeats - $roomBronSeats,
                'roomOpenNoStatusSeats' => $roomOpenSeats - $roomTakenSeats - $roomBronSeats,
                'roomTakenSeats' => $roomTakenSeats,
                'roomBronSeats' => $roomBronSeats,
                'roomCustomBoxes' => $roomCustomBoxes
            );
        }

        $statsArray['seatsTotal'] = $howManyRegSeats;
        $statsArray['openSeats'] = $howManyOpenSeats - $howManyBronSeats - $howManyTakenSeats;
        $statsArray['bronSeats'] = $howManyBronSeats;
        $statsArray['takenSeats'] = $howManyTakenSeats;
        $statsArray['roomCount'] = $roomCount;
        $statsArray['roomsInfo'] = $roomsInfo;

        return $statsArray;
    }
}