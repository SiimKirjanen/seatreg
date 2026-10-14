<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregLockService {
    /**
     *
     * A MySQL named lock, for work two requests must not do at the same moment. MySQL also lets
     * go of it when the request ends.
     *
     * @param string $name What the lock guards, e.g. seats_<registration code>
     * @return bool false when another request held it for longer than SEATREG_LOCK_TIMEOUT_SECONDS
     *
     */
    public static function lock($name) {
        global $wpdb;

        return $wpdb->get_var( $wpdb->prepare('SELECT GET_LOCK(%s, %d)', self::lockName($name), SEATREG_LOCK_TIMEOUT_SECONDS) ) === '1';
    }

    public static function unlock($name) {
        global $wpdb;

        $wpdb->query( $wpdb->prepare('SELECT RELEASE_LOCK(%s)', self::lockName($name)) );
    }

    //Lock names are shared by every database on the MySQL server and capped at 64 characters
    private static function lockName($name) {
        global $wpdb;

        return 'seatreg_' . md5( DB_NAME . $wpdb->prefix . $name );
    }
}
