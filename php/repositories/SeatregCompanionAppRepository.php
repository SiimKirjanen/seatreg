<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregCompanionAppRepository {
    public static function isCompanionAppEnabled() {
        $enabled = get_option(SEATREG_COMPANION_APP_ENABLED, false);

        return (bool) $enabled;
    }
}