<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregLayoutService {
    public static function hideSensitiveData($layout) {   
       $layout = json_decode($layout); 

       foreach( $layout->roomData as $roomData ) {
            foreach( $roomData->boxes as $box ) {
                if($box->password) {
                    $box->password = true;
                }else {
                    $box->password = false;
                }
            }
       }
       
       return json_encode($layout);
    }
}