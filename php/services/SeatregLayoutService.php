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
}