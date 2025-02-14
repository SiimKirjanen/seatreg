<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregUploadsRepository {
    /**
     *
     * Returns URL of the directory where custom payment icons are stored
     *
     */
    public static function getCustomPaymentIconLocationURL($registrationCode) {
        return SEATREG_TEMP_FOLDER_URL . '/custom_payment_icons/' . $registrationCode;
    }

    /**
     *
     * Returns DIR of the directory where custom payment icons are stored
     *
     */
    public static function getCustomPaymentIconLocationDir($registrationCode) {
        return SEATREG_TEMP_FOLDER_DIR . '/custom_payment_icons/' . $registrationCode;
    }

    /**
     *
     * Returns DIR of the directory where custom room images are stored
     *
     */
    public static function getCustomRoomImagesLocationDir($registrationCode) {
        return SEATREG_TEMP_FOLDER_DIR . '/room_images/' . $registrationCode;
    }

}