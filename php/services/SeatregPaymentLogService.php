<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregPaymentLogService {
    public static function log($bookingId, $logMessage, $logStatus = SEATREG_PAYMENT_LOG_OK) {
        global $seatreg_db_table_names;
		global $wpdb;

		return $wpdb->insert(
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