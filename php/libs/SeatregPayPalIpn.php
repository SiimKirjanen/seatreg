<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

 class SeatregPayPalIpn {
	private $_url;
	private $_businessEmail;
	private $_currency;
	private $_price;
	private $_bookingId;

	public function __construct($isSandbox, $businessEmail, $currency, $price, $bookingId) {
		if ( !$isSandbox ) {
			$this->_url = SEATREG_PAYPAL_IPN;
		} else {
			$this->_url = SEATREG_PAYPAL_IPN_SANDBOX;
		}
        $this->_businessEmail = $businessEmail;
        $this->_currency = $currency;
		$this->_price = $price;
		$this->_bookingId = $bookingId;
	}

	public function run() {
		$postFields = 'cmd=_notify-validate';
		
		foreach($_POST as $key => $value) {
			$postFields .= "&$key=" . urlencode($value);
		}
	
		$ch = curl_init();

		curl_setopt_array($ch, array(
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
			CURLOPT_CONNECTTIMEOUT => 30,

		));
		
		$result = curl_exec($ch);
		curl_close($ch);

		if (strcmp ($result , "VERIFIED") == 0) {
   			// The IPN is verified
   			if($this->emailCheck()) {
   				if($this->currencyAndAmountCheck()) {
   					if($this->statusCheck()) {
   						if($this->txn_idCheck()) {
   							$this->insertPayment();
   						}
   					}
   				}
   			}

		} else if (strcmp ($result , "INVALID") == 0) {
		    // IPN invalid, log for manual investigation
			$this->sendError('IPN invalid error');
		    exit();
		}
	}

	private function insertPayment() {
		seatreg_insert_update_payment($this->_bookingId, SEATREG_PAYMENT_COMPLETED, $_POST['txn_id']);
	}

	private function txn_idCheck() {
		// check that txn_id has not been previously processed
		$payment = seatreg_get_processed_payment($bookingId);

		if( !$payment ) {
			return true;
		}else {
			$this->sendError('txn_id has been previously processed error');

			return false;
		}
		
	}

	private function statusCheck() {
		// check whether the payment_status is Completed
		if($_POST['payment_status'] == 'Completed') {
			return true;
		}else {
			$this->sendError('Payment_status is different');

			return false;
		}

	}

	private function currencyAndAmountCheck() {
		// check that payment_amount/payment_currency are correct
		if($_POST['mc_currency'] == $this->_currency && $_POST['mc_gross'] == $this->_price) {
			return true;
		}else {
			$this->sendError('Payment_amount/payment_currency in not correct error');

			return false;
		}

	}

	private function emailCheck() {
		// check that receiver_email is your Primary PayPal email
		if($_POST['receiver_email'] === $this->_businessEmail) {
			return true;
		}else {
			$this->sendError('Receiver_email is not my Primary PayPal email error');

			return false;
		}
	}

	private function sendError($errorHeading) {
	}

}