<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregImageCopyService {
    /**
     * Helper method to copy files from one directory to another.
     *
     * @param string $sourceDir The source directory.
     * @param string $destinationDir The destination directory.
     * @return bool True on success, false on failure.
     */
    private static function copyFiles($sourceDir, $destinationDir) {

        if ( !file_exists($sourceDir) ) {
            // If the source directory does not exist, return true as nothing to copy
            return true;
        }

        if ( !file_exists($destinationDir) ) {
            $dirCreated = mkdir($destinationDir, 0755, true);

            if ( !$dirCreated ) {
                return false;
            }
        }

        $dir = opendir($sourceDir);

        while (false !== ($file = readdir($dir))) {
            // Skip the current and parent directory entries
            if ($file != '.' && $file != '..') {
                // Copy each file to the destination directory
                if (!copy($sourceDir . '/' . $file, $destinationDir . '/' . $file)) {
                    // If any file fails to copy, return false
                    return false;
                }
            }
        }

        closedir($dir);

        return true;
    }

    public static function copyRegistrationRoomImages($targetRegistrationCode, $destinationRegistrationCode) {
        $targetCustomRoomImagesLocation = SeatregUploadsRepository::getCustomRoomImagesLocationDir($targetRegistrationCode);
        $destinationCustomRoomImagesLocation = SeatregUploadsRepository::getCustomRoomImagesLocationDir($destinationRegistrationCode);

        return self::copyFiles($targetCustomRoomImagesLocation, $destinationCustomRoomImagesLocation);
    }

    public static function copyRegistrationPaymentImages($targetRegistrationCode, $destinationRegistrationCode) {
        $targetCustomRoomImagesLocation = SeatregUploadsRepository::getCustomPaymentIconLocationDir($targetRegistrationCode);
        $destinationCustomRoomImagesLocation = SeatregUploadsRepository::getCustomPaymentIconLocationDir($destinationRegistrationCode);

        return self::copyFiles($targetCustomRoomImagesLocation, $destinationCustomRoomImagesLocation);
    }

    public static function copyAllRegistrationImages($targetRegistrationCode, $destinationRegistrationCode) {
        $roomImagesCopied = self::copyRegistrationRoomImages($targetRegistrationCode, $destinationRegistrationCode);
        $paymentImagesCopied = self::copyRegistrationPaymentImages($targetRegistrationCode, $destinationRegistrationCode);

        return $roomImagesCopied && $paymentImagesCopied;
    }
}