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

    public static function validateRoomAndSeatId($layout, $bookingRoomName, $bookingSeatId) {
        $status = (object) [
            'valid' => false,
            'searchStatus' => '',
            'errorText' => ''
        ];

        foreach( $layout as $layoutData ) {
            $room = $layoutData->room;
            $status->searchStatus = 'room-searching';
            $status->errorText = sprintf( esc_html__('Room %s does not exist!', 'seatreg'),  esc_html($bookingRoomName) );
    
            if( $room->name == $bookingRoomName ) {
                $status->searchStatus = 'seat-id-searching';
                $status->errorText = sprintf( esc_html__('Seat id %s does not exist in %s', 'seatreg'),  esc_html($bookingSeatId), esc_html($bookingRoomName) );
                
                foreach( $layoutData->boxes as $box ) {    
                    if( $box->canRegister == 'true' && $box->id == $bookingSeatId ) {
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

    public static function getRoomsLength($roomData) {
        return count($roomData);
    }
}