<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregLinksService {
    public static function getRegistrationURL() {    
        return get_site_url() . '/seatreg';
    }
}