<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregImageDeleteService {
    public static function deleteCustomPaymentImage($registrationCode, $fileName) {
        $customPaymentLocation = SeatregUploadsRepository::getCustomPaymentIconLocationDir($registrationCode);
        $imgPath = $customPaymentLocation . '/' . $fileName;
		
		return unlink($imgPath);
    }
}