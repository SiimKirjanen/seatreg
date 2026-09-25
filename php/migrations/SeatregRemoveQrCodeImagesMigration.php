<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregRemoveQrCodeImagesMigration {
    /**
     *
     * Remove the QR code images earlier versions left in the uploads folder. Each was named after
     * the booking id it was made for, and the folder is public, so they gave the booking ids away.
     *
     * Only files named like a booking id are touched, so running this again does nothing.
     *
     */
    public static function run() {
        $images = glob(SEATREG_TEMP_FOLDER_DIR . '/*.png');

        if( !$images ) {
            return;
        }

        foreach( $images as $image ) {
            if( preg_match('/^[0-9a-f]{40}\.png$/', basename($image)) === 1 && !unlink($image) ) {
                error_log('SeatReg: removing an old QR code image from the uploads folder failed');
            }
        }
    }
}
