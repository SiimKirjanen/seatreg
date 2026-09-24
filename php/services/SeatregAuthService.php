<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregAuthService {
     /**
     *
     * Check if current visitor is logged in
     * * @param boolean True if user is logged in, false if not logged in.
     *
    */
    public static function isLoggedIn() {
        return is_user_logged_in();
    }

     /**
     *
     * Get logged in user ID
     * * @param int The current user’s ID, or 0 if no user is logged in.
     *
    */
    public static function getCurrentUserId() {
        return get_current_user_id();
    }

     /**
     *
     * Check the password a visitor entered against the registration password
     * * @param boolean True if the registration has no password or the password matches.
     *
    */
    public static function registrationPasswordMatches($registrationPassword, $submittedPassword) {
        if( $registrationPassword === null || $registrationPassword === '' ) {
            return true;
        }

        return is_string($submittedPassword) && hash_equals($registrationPassword, $submittedPassword);
    }
}