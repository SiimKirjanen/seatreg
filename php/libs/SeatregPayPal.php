<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

 class SeatregPayPal {
	private $_url;
	private $_businessEmail;
	private $_currency;
	private $_price;

	public function __construct($isSandbox, $businessEmail, $currency, $price) {
		if ( !$isSandbox ) {
			$this->_url = SEATREG_PAYPAL_FORM_ACTION;
		} else {
			$this->_url = SEATREG_PAYPAL_FORM_ACTION_SANDBOX;
		}
        $this->_businessEmail = $businessEmail;
        $this->_currency = $currency;
		$this->_price = $price;
	}

	public function run() {
		$postFields = 'cmd=_notify-validate';
		
		foreach($_POST as $key => $value) {
			$postFields .= "&$key=" . urlencode(stripslashes($value));
		}
	
		$ch = curl_init();

		curl_setopt_array($ch, array(
			CURLOPT_URL => $this->_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $postFields
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
	}

	private function txn_idCheck() {
		// check that txn_id has not been previously processed
		global $db;

		$stmt = $db -> prepare('SELECT * FROM paypal_payments WHERE txn_id = :id');
		$stmt->execute(array(':id'=>$_POST['txn_id']));

		if($stmt ->rowCount() == 0) {
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