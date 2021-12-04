<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregRegistrationService {
    /**
     *
     * Return seat price from registration layout
     *
    */
    public static function getSeatPriceFromLayout($seatID, $roomUUID, $roomsData) {
        $price = 0;

        foreach($roomsData as $roomData) {
            if($roomData->room->uuid === $roomUUID) {
                foreach($roomData->boxes as $box) {
                    if($box->id === $seatID) {
                        $price = $box->price;

                        break 2;
                    }
                }
            }
        }

	    return $price;
    }

    /**
     *
     * Return room name from layout
     *
    */
    public static function getRoomNameFromLayout($roomsLayout, $bookingRoomUuid) {
        $roomName = null;

        foreach($roomsLayout as $roomLayout) {
            if($roomLayout->room->uuid === $bookingRoomUuid) {
                $roomName = $roomLayout->room->name;
            }
        }

        return $roomName;
    }

    public static function updateRegistrationLayout($registrationLayout, $registrationCode) {
        global $wpdb;
	    global $seatreg_db_table_names;

        return $wpdb->update(
            "$seatreg_db_table_names->table_seatreg",
            array(
                'registration_layout' => $registrationLayout
            ),
            array(
                'registration_code' => $registrationCode
            ),
            array('%s'),
            array('%s')
        );
    }
}