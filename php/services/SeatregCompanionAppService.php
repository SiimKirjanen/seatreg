<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregCompanionAppService {
     public static function setEnabled( bool $enabled ): void {
        update_option(
            SEATREG_COMPANION_APP_ENABLED,
            $enabled ? 1 : 0
        );
    }
}