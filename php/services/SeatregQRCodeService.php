<?php
require_once(SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/phpqrcode/qrlib.php');

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregRegQRCodeService {
    /**
     *
     * Generate QR image into a temporary file outside the uploads folder. The caller deletes the file.
     * Null when no temporary file could be made.
     *
    */
    public static function generateQRCodeImage($qrContent) {
        $qrFile = tempnam(get_temp_dir(), 'seatreg-qr');

        if( $qrFile === false ) {
            return null;
        }

        QRcode::png($qrContent, $qrFile, QR_ECLEVEL_L, 4);

        return $qrFile;
    }

    /**
     *
     * Generate QR image and return its PNG data. Null when it could not be made.
     *
    */
    public static function generateQRCodeImageData($qrContent) {
        $qrFile = self::generateQRCodeImage($qrContent);

        if( $qrFile === null ) {
            return null;
        }

        $qrImage = file_get_contents($qrFile);
        unlink($qrFile);

        return $qrImage ? $qrImage : null;
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