<?php
require_once(SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/phpqrcode/qrlib.php');

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregRegQRCodeService {
    /**
     *
     * Generate QR Image
     *
    */
    public static function generateQRCodeImage($qrContent, $bookingId) {
        QRcode::png($qrContent, SEATREG_TEMP_FOLDER_DIR. '/' . $bookingId . '.png', QR_ECLEVEL_L, 4);
    }

    /**
     *
     * Get the QR Code content
     *
    */
    public static function getQRCodeContent($bookingId, $registrationCode, $qrType) {
        $bookingCheckURL = get_site_url() . '?seatreg=booking-status&registration=' . $registrationCode . '&id=' . $bookingId;
        $qrContent = $qrType === 'booking-id' ? $bookingId : $bookingCheckURL;

        return $qrContent;
    }
}