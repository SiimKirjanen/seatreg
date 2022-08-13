<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/payment/SeatregPaymentBase.php' );
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_functions.php' );
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/emails.php' );

 class SeatregPayPalIpn extends SeatregPaymentBase {
	private $_url;
	private $_businessEmail;
	private $_ipnVerificationRetry = 5;

	public function __construct($isSandbox, $businessEmail, $currency, $price, $bookingId, $setBookingConfirmed, $registrationCode) {
		parent::__construct($currency, $price, $bookingId, $setBookingConfirmed, $registrationCode, 'PayPal');

		if ( !$isSandbox ) {
			$this->_url = SEATREG_PAYPAL_IPN;
		} else {
			$this->_url = SEATREG_PAYPAL_IPN_SANDBOX;
		}

        $this->_businessEmail = $businessEmail;
	}

	public function run() {
		if($this->ipnVerification()) {
			if($this->emailCheck()) {
				if($this->statusCheck()) {
					if($this->currencyAndAmountCheck( $_POST['mc_currency'], $_POST['mc_gross'] )) {
						if($this->paymentDoneCheck()) {
							$this->insertPayment($_POST['txn_id']);
						}
					}
				}
			}	
		}
	}

	private function ipnVerification() {
		$this->log(esc_html__('Starting IPN verification', 'seatreg'));
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
			$result = trim(curl_exec($ch));
			curl_close($ch);

			if (strcmp ($result , "VERIFIED") == 0) {
				// The IPN is verified
				 $this->log(esc_html__('The IPN is verified', 'seatreg'));
	
				 $isVerified = true;
				 $gotCurlIpnResponse = true;
				 $retryCounter = 999;			 
			} else if (strcmp ($result , "INVALID") == 0) {
				// IPN invalid, log for manual investigation
				$this->log(esc_html__('The IPN is invalid', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);
				$this->changePaymentStatus(SEATREG_PAYMENT_VALIDATION_FAILED);
				$gotCurlIpnResponse = true;
				$retryCounter = 999;
			}else {
				$this->log(sprintf(esc_html__('Unknown response from IPN. Will try again %s', 'seatreg'),  $result), SEATREG_PAYMENT_LOG_ERROR);
			}
		}

		if($retryCounter >= $this->_ipnVerificationRetry && !$gotCurlIpnResponse) {
			//something unexpected happened. Did not got response from IPN. Return non 200 so IPN will try again later
			$this->log(esc_html__('IPN retry logic failed. Will try again later', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);
			header("HTTP/1.1 500");  
	
			exit();
		}

		return $isVerified;
	}

	private function statusCheck() {
		// check whether the payment_status is Completed or something else happened
		if( isset($_POST['payment_status']) && $_POST['payment_status'] == 'Completed' ) {
			return true;
		}elseif( isset($_POST['payment_status']) && $_POST['payment_status'] == 'Reversed' ) {
			$this->changePaymentStatus(SEATREG_PAYMENT_REVERSED);
			$this->changeBookingStatus(SEATREG_BOOKING_DEFAULT);
			seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to 0 state by the system (PayPal payment reversed)', false);
			$this->log(esc_html__('Payment is reversed', 'seatreg'), SEATREG_PAYMENT_LOG_INFO);
			
			return false;
		}elseif( isset($_POST['payment_status']) && $_POST['payment_status'] == 'Refunded' ) {
			$this->changePaymentStatus(SEATREG_PAYMENT_REFUNDED);
			$this->changeBookingStatus(SEATREG_BOOKING_DEFAULT);
			seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to 0 state by the system (PayPal payment refunded)', false);
			$this->log(esc_html__('Payment was refunded', 'seatreg'), SEATREG_PAYMENT_LOG_INFO);
			
			return false;
		}elseif( isset($_POST['case_type']) ) {
			$this->log(sprintf(esc_html__('Got a %s case. Reason is %s. Case id: %s', 'seatreg'), $_POST['case_type'], $_POST['reason_code'], $_POST['case_id']), SEATREG_PAYMENT_LOG_INFO);

			return false;
		}

		return false;
	}

	private function emailCheck() {
		// check that receiver_email is your Primary PayPal email
		if($_POST['receiver_email'] === $this->_businessEmail) {
			return true;
		}else {
			$this->log(sprintf(esc_html__("Receiver_email %s is not my Primary PayPal email %s", 'seatreg'), $_POST['receiver_email'], $this->_businessEmail), SEATREG_PAYMENT_LOG_ERROR);
			$this->changePaymentStatus(SEATREG_PAYMENT_VALIDATION_FAILED);

			return false;
		}
	}
}