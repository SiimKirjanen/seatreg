<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_functions.php' );
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/emails.php' );

 class SeatregPayPalIpn {
	private $_url;
	private $_businessEmail;
	private $_currency;
	private $_price;
	private $_bookingId;
	private $_setBookingConfirmed;
	private $_ipnVerificationRetry = 5;
	private $_registrationCode;

	public function __construct($isSandbox, $businessEmail, $currency, $price, $bookingId, $setBookingConfirmed, $registrationCode) {
		if ( !$isSandbox ) {
			$this->_url = SEATREG_PAYPAL_IPN;
		} else {
			$this->_url = SEATREG_PAYPAL_IPN_SANDBOX;
		}
        $this->_businessEmail = $businessEmail;
        $this->_currency = $currency;
		$this->_price = $price;
		$this->_bookingId = $bookingId;
		$this->_setBookingConfirmed = $setBookingConfirmed;
		$this->_registrationCode = $registrationCode;
	}

	public function run() {
		$isIpnVerified = $this->ipnVerification();

		if($isIpnVerified) {
			if($this->emailCheck()) {
				if($this->statusCheck()) {
					if($this->currencyAndAmountCheck()) {
						if($this->txn_idCheck()) {
							$this->insertPayment();
						}
					}
				}
			}	
		}

	}

	private function ipnVerification() {
		$this->log($this->_bookingId, esc_html__('Starting IPN verification', 'seatreg'));
		$postFields = 'cmd=_notify-validate';
		$retryCounter = 0;
		$gotCurlIpnResponse = false;
		$isVerified = false;
		
		foreach($_POST as $key => $value) {
			$postFields .= "&$key=" . urlencode($value);
		}

		while( $retryCounter < $this->_ipnVerificationRetry ) {
			$retryCounter++;
			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_URL => $this->_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSLVERSION => 6,
				CURLOPT_SSL_VERIFYPEER => 1,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $postFields,
				CURLOPT_HTTPHEADER => array(
					'User-Agent: PHP-IPN-Verification-Script',
					'Connection: Close',
				),
				CURLOPT_FORBID_REUSE => 1,
				CURLOPT_CONNECTTIMEOUT => 100,
	
			));
			$result = curl_exec($ch);
			curl_close($ch);

			if (strcmp ($result , "VERIFIED") == 0) {
				// The IPN is verified
				 $this->log($this->_bookingId, esc_html__('The IPN is verified', 'seatreg'));
	
				 $isVerified = true;
				 $gotCurlIpnResponse = true;
				 $retryCounter = 999;			 
			} else if (strcmp ($result , "INVALID") == 0) {
				// IPN invalid, log for manual investigation
				$this->log($this->_bookingId, esc_html__('The IPN is invalid', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);
				$this->changePaymentStatus(SEATREG_PAYMENT_VALIDATION_FAILED);
				$gotCurlIpnResponse = true;
				$retryCounter = 999;
			}else {
				$this->log($this->_bookingId, sprintf(esc_html__('Unknown response from IPN. Will try again %s', 'seatreg'),  $result), SEATREG_PAYMENT_LOG_ERROR);
			}
		}

		if($retryCounter >= $this->_ipnVerificationRetry && !$gotCurlIpnResponse) {
			//something unexpected happened. Did not got response from IPN. Return non 200 so IPN will try again later
			$this->log($this->_bookingId, esc_html__('IPN retry logic failed. Will try again later', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);
			header("HTTP/1.1 500");  
	
			exit();
		}

		return $isVerified;
	}

	private function insertPayment() {
		SeatregPaymentService::insertOrUpdatePayment($this->_bookingId, SEATREG_PAYMENT_COMPLETED, $_POST['txn_id'], $_POST['mc_currency'], $_POST['mc_gross']);
		$this->log($this->_bookingId, sprintf(esc_html__('Payment for %s is completed', 'seatreg'), "$this->_price $this->_currency"));

		if($this->_setBookingConfirmed === '1') {
			$this->changeBookingStatus(2);
			seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to approved state by the system (PayPal IPN)', false);
			seatreg_send_approved_booking_email($this->_bookingId, $this->_registrationCode);
		}
	}

	private function txn_idCheck() {
		// check that txn_id has not been previously processed
		$payment = SeatregPaymentRepository::getProcessedPaymentsByBookingId( $this->_bookingId );

		if( !$payment ) {
			return true;
		}else {
			$this->log($this->_bookingId, esc_html__('txn_id has been previously processed', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);

			return false;
		}
	}

	private function statusCheck() {
		// check whether the payment_status is Completed or something else happened
		if( isset($_POST['payment_status']) && $_POST['payment_status'] == 'Completed' ) {
			return true;
		}elseif( isset($_POST['payment_status']) && $_POST['payment_status'] == 'Reversed' ) {
			$this->changePaymentStatus(SEATREG_PAYMENT_REVERSED);
			$this->changeBookingStatus(0);
			seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to 0 state by the system (PayPal payment reversed)', false);
			$this->log($this->_bookingId, esc_html__('Payment is reversed', 'seatreg'), SEATREG_PAYMENT_LOG_INFO);
			
			return false;
		}elseif( isset($_POST['payment_status']) && $_POST['payment_status'] == 'Refunded' ) {
			$this->changePaymentStatus(SEATREG_PAYMENT_REFUNDED);
			$this->changeBookingStatus(0);
			seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to 0 state by the system (PayPal payment refunded)', false);
			$this->log($this->_bookingId, esc_html__('Payment was refunded', 'seatreg'), SEATREG_PAYMENT_LOG_INFO);
			
			return false;
		}elseif( isset($_POST['case_type']) ) {
			$this->log($this->_bookingId, sprintf(esc_html__('Got a %s case. Reason is %s. Case id: %s', 'seatreg'), $_POST['case_type'], $_POST['reason_code'], $_POST['case_id']), SEATREG_PAYMENT_LOG_INFO);

			return false;
		}

		return false;
	}

	private function currencyAndAmountCheck() {
		// check that payment_amount/payment_currency are correct
		if($_POST['mc_currency'] == $this->_currency && $_POST['mc_gross'] == $this->_price) {
			return true;
		}else {
			$this->log($this->_bookingId, sprintf(esc_html__('Payment %s is not correct. Expecting %s', 'seatreg'), $_POST['mc_gross'] . ' ' . $_POST['mc_currency'], $this->_price . ' ' . $this->_currency ), SEATREG_PAYMENT_LOG_ERROR);
			$this->changePaymentStatus(SEATREG_PAYMENT_VALIDATION_FAILED);

			return false;
		}

	}

	private function emailCheck() {
		// check that receiver_email is your Primary PayPal email
		if($_POST['receiver_email'] === $this->_businessEmail) {
			return true;
		}else {
			$this->log($this->_bookingId, sprintf(esc_html__("Receiver_email %s is not my Primary PayPal email %s", 'seatreg'), $_POST['receiver_email'], $this->_businessEmail), SEATREG_PAYMENT_LOG_ERROR);
			$this->changePaymentStatus(SEATREG_PAYMENT_VALIDATION_FAILED);

			return false;
		}
	}

	private function log($bookingId, $logMessage, $logStatus = SEATREG_PAYMENT_LOG_OK) {
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

	private function changeBookingStatus($status = 2) {
		SeatregBookingService::changeBookingStatus($status, $this->_bookingId);
	}

	private function changePaymentStatus($status = SEATREG_PAYMENT_COMPLETED) {
		SeatregPaymentService::changePaymentStatus($status, $this->_bookingId);
	}
}