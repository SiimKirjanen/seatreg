<?php

class SeatregPaymentBase {
    protected $_currency;
    protected $_price;
    protected $_bookingId;
    protected $_setBookingConfirmed;
    protected $_registrationCode;
    protected $_paymentMethod;

    public function __construct($currency, $price, $bookingId, $setBookingConfirmed, $registrationCode, $paymentMethod) {
        $this->_currency = $currency;
        $this->_price = $price;
        $this->_bookingId = $bookingId;
        $this->_setBookingConfirmed = $setBookingConfirmed;
        $this->_registrationCode = $registrationCode;
        $this->_paymentMethod = $paymentMethod;
    }

    protected function log($logMessage, $logStatus = SEATREG_PAYMENT_LOG_OK) {
		global $seatreg_db_table_names;
		global $wpdb;

		$wpdb->insert(
			$seatreg_db_table_names->table_seatreg_payments_log,
			array(
				'booking_id' => $this->_bookingId,
				'log_message' => $logMessage,
				'log_status' => $logStatus
			),
			'%s'
		);
	}

    protected function changeBookingStatus($status = 2) {
		SeatregBookingService::changeBookingStatus($status, $this->_bookingId);
	}

    protected function changePaymentStatus($status = SEATREG_PAYMENT_COMPLETED) {
		SeatregPaymentService::changePaymentStatus($status, $this->_bookingId);
	}

    protected function insertPayment($txnId) {
		SeatregPaymentService::insertOrUpdatePayment($this->_bookingId, SEATREG_PAYMENT_COMPLETED, $txnId, $this->_currency, $this->_price);
		$this->log(sprintf(esc_html__('Payment for %s is completed via %s', 'seatreg'), "$this->_price $this->_currency", $this->_paymentMethod));

		if($this->_setBookingConfirmed === '1') {
			$bookingData = SeatregBookingRepository::getDataRelatedToBooking($this->_bookingId);

			$this->changeBookingStatus(SEATREG_BOOKING_APPROVED);
			seatreg_add_activity_log('booking', $this->_bookingId, "Booking set to approved state by the system ($this->_paymentMethod)", false);
			seatreg_send_approved_booking_email($this->_bookingId, $this->_registrationCode, $bookingData->approved_booking_email_template);
		}
	}

    protected function paymentDoneCheck() {
		// check that payment has not been previously processed
		$payment = SeatregPaymentRepository::getProcessedPaymentsByBookingId( $this->_bookingId );

		if( !$payment ) {
			return true;
		}else {
			$this->log(esc_html__('Payment has been previously processed', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);

			return false;
		}
	}

    protected function currencyAndAmountCheck($receivedCurrency, $receivedPrice) {
		// check that payment_amount/payment_currency are correct
		if( strtoupper($receivedCurrency) === $this->_currency && floatval($receivedPrice) === floatval($this->_price) ) {
			return true;
		}else {
			$this->log(sprintf(esc_html__('Payment %s is not correct. Expecting %s', 'seatreg'), $receivedPrice . ' ' .  $receivedCurrency, $this->_price . ' ' . $this->_currency ), SEATREG_PAYMENT_LOG_ERROR);
			$this->changePaymentStatus(SEATREG_PAYMENT_VALIDATION_FAILED);

			return false;
		}

	}
}