<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregImageDeleteService {
    public static function deleteCustomPaymentImage($registrationCode, $fileName) {
        if (!SeatregSanitizationService::isValidRegistrationCode($registrationCode)) {
            return false;
        }

        $customPaymentLocation = SeatregUploadsRepository::getCustomPaymentIconLocationDir($registrationCode);
        $imgPath = SeatregSanitizationService::resolvePathInsideBase($customPaymentLocation, $fileName);

        if ($imgPath === false) {
            return false;
        }

        return unlink($imgPath);
    }
}