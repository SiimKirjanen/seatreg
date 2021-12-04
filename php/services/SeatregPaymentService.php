<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregPaymentService {
    /**
     *
     * Return seat price from registration layout
     *
    */
    public static function insertProcessingPayment($bookingId) {
        global $seatreg_db_table_names;
        global $wpdb;
    
        $alreadyInserted = SeatregPaymentRepository::getPaymentByBookingId($bookingId);
    
        if( !$alreadyInserted ) {
            $wpdb->insert(
                $seatreg_db_table_names->table_seatreg_payments,
                array(
                    'booking_id' => $bookingId,
                    'payment_status' => SEATREG_PAYMENT_PROCESSING
                ),
                '%s'
            );
            self::insertPaymentLog($bookingId, 'PayPal return to merchant', 'ok');
        }
    }
    /**
     *
     * Insert payment log
     *
    */
    public static function insertPaymentLog($bookingId, $logMessage, $logStatus) {
        global $seatreg_db_table_names;
        global $wpdb;

        $wpdb->insert(
            $seatreg_db_table_names->table_seatreg_payments_log,
            array(
                'booking_id' => $bookingId,
                'log_message' => $logMessage,
                'log_status' => $logStatus
            ),
            '%s'
        );
    }
}