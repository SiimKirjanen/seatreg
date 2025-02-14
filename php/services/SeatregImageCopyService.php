<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregImageCopyService {
    public static function copyRegistrationRoomImages($targetRgistrationCode, $destinationRegistrationCode) {
        $targetCustomRoomImagesLocation = SeatregUploadsRepository::getCustomRoomImagesLocationDir($targetRgistrationCode);
        $destinationCustomRoomImagesLocation = SeatregUploadsRepository::getCustomRoomImagesLocationDir($destinationRegistrationCode);

        if ( !file_exists($destinationCustomRoomImagesLocation) ) {
            $dirCreated = mkdir($destinationCustomRoomImagesLocation, 0755, true);

            if ( !$dirCreated ) {
                return false;
            }
        }

        $dir = opendir($targetCustomRoomImagesLocation);

        while (false !== ($file = readdir($dir))) {
            // Skip the current and parent directory entries
            if ($file != '.' && $file != '..') {
                // Copy each file to the destination directory
                if (!copy($targetCustomRoomImagesLocation . '/' . $file, $destinationCustomRoomImagesLocation . '/' . $file)) {
                    // If any file fails to copy, return false
                    return false;
                }
            }
        }

        closedir($dir);

        return true;
    }
}