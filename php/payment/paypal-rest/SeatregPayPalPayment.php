<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/payment/SeatregPaymentBase.php' );

/**
 *
 * Handles PayPal REST API webhook events (Orders v2).
 * Replacement for the legacy IPN handler in php/payment/paypal/SeatregPayPalIpn.php
 *
 */
class SeatregPayPalPayment extends SeatregPaymentBase {
	private $_clientId;
	private $_clientSecret;
	private $_sandbox;
	private $_webhookId;
	private $_rawPayload;
	private $_event;

	public function __construct($currency, $price, $bookingId, $setBookingConfirmed, $registrationCode, $clientId, $clientSecret, $sandbox, $webhookId, $rawPayload) {
		parent::__construct($currency, $price, $bookingId, $setBookingConfirmed, $registrationCode, 'PayPal');

		$this->_clientId = $clientId;
		$this->_clientSecret = $clientSecret;
		$this->_sandbox = $sandbox;
		$this->_webhookId = $webhookId;
		$this->_rawPayload = $rawPayload;
		$this->_event = json_decode($rawPayload);
	}

	public function run() {
		if($this->webhookSignatureCheck()) {
			if($this->statusCheck()) {
				$resource = $this->_event->resource;

				if($this->currencyAndAmountCheck($resource->amount->currency_code, $resource->amount->value)) {
					if($this->paymentDoneCheck()) {
						$this->insertPayment($resource->id);
						http_response_code(200);
					}
				}
			}
		}
	}

	/**
	 *
	 * Verify the webhook signature with the PayPal verify-webhook-signature API
	 *
	 */
	private function webhookSignatureCheck() {
		if( !$this->_webhookId ) {
			SeatregPaymentLogService::log($this->_bookingId, esc_html__('PayPal webhook id is missing. Please save PayPal payment settings again.', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);
			http_response_code(400);
			exit();
		}

		$authAlgo = isset($_SERVER['HTTP_PAYPAL_AUTH_ALGO']) ? $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] : '';
		$certUrl = isset($_SERVER['HTTP_PAYPAL_CERT_URL']) ? $_SERVER['HTTP_PAYPAL_CERT_URL'] : '';
		$transmissionId = isset($_SERVER['HTTP_PAYPAL_TRANSMISSION_ID']) ? $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] : '';
		$transmissionSig = isset($_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG']) ? $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] : '';
		$transmissionTime = isset($_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME']) ? $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] : '';

		if( !$authAlgo || !$certUrl || !$transmissionId || !$transmissionSig || !$transmissionTime ) {
			SeatregPaymentLogService::log($this->_bookingId, esc_html__('PayPal webhook signature headers are missing', 'seatreg'), SEATREG_PAYMENT_VALIDATION_FAILED);
			http_response_code(400);
			exit();
		}

		if( !self::isPayPalCertUrl($certUrl) ) {
			SeatregPaymentLogService::log($this->_bookingId, esc_html__('PayPal webhook certificate url is not a PayPal address', 'seatreg'), SEATREG_PAYMENT_VALIDATION_FAILED);
			http_response_code(400);
			exit();
		}

		/* translators: %s: PayPal webhook event type */
		SeatregPaymentLogService::log($this->_bookingId, sprintf(esc_html__('Starting to verify PayPal webhook signature for %s', 'seatreg'), $this->getEventType()));

		//The webhook event has to be sent back exactly as PayPal sent it. Decoding and re-encoding
		//it can change number and unicode formatting, which makes the verification fail.
		$requestBody = '{'
			. '"auth_algo":' . wp_json_encode($authAlgo) . ','
			. '"cert_url":' . wp_json_encode($certUrl) . ','
			. '"transmission_id":' . wp_json_encode($transmissionId) . ','
			. '"transmission_sig":' . wp_json_encode($transmissionSig) . ','
			. '"transmission_time":' . wp_json_encode($transmissionTime) . ','
			. '"webhook_id":' . wp_json_encode($this->_webhookId) . ','
			. '"webhook_event":' . $this->_rawPayload
			. '}';

		$response = SeatregPayPalApiService::request(
			'POST',
			'/v1/notifications/verify-webhook-signature',
			$requestBody,
			$this->_clientId,
			$this->_clientSecret,
			$this->_sandbox
		);

		if( !$response->success ) {
			/* translators: %s: error message */
			SeatregPaymentLogService::log($this->_bookingId, sprintf(esc_html__('Could not verify PayPal webhook signature. %s', 'seatreg'), $response->error), SEATREG_PAYMENT_LOG_ERROR);
			//Answer with an error so that PayPal sends the event again later
			http_response_code(500);
			exit();
		}

		if( !isset($response->body->verification_status) || $response->body->verification_status !== 'SUCCESS' ) {
			SeatregPaymentLogService::log($this->_bookingId, esc_html__('PayPal webhook signature is invalid', 'seatreg'), SEATREG_PAYMENT_VALIDATION_FAILED);
			http_response_code(400);
			exit();
		}

		SeatregPaymentLogService::log($this->_bookingId, esc_html__('PayPal webhook signature verified', 'seatreg'));

		return true;
	}

	/**
	 *
	 * The type of the webhook event that is being handled
	 *
	 */
	private function getEventType() {
		return isset($this->_event->event_type) ? $this->_event->event_type : '';
	}

	private function statusCheck() {
		switch($this->getEventType()) {
			case 'PAYMENT.CAPTURE.COMPLETED':

				return true;
			case 'CHECKOUT.ORDER.APPROVED':
				//The buyer approved the order but did not return to the site. Capturing it here
				//leads to a PAYMENT.CAPTURE.COMPLETED event, which records the payment.
				$this->captureApprovedOrder();

				return false;
			case 'PAYMENT.CAPTURE.DENIED':
				$this->changePaymentStatus(SEATREG_PAYMENT_ERROR);
				$this->log(esc_html__('Payment failed', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);

				return false;
			case 'PAYMENT.CAPTURE.REFUNDED':
				$this->changePaymentStatus(SEATREG_PAYMENT_REFUNDED);
				$this->changeBookingStatus(SEATREG_BOOKING_DEFAULT);
				seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to 0 state by the system (PayPal payment refunded)', false);
				$this->log(esc_html__('Payment was refunded', 'seatreg'), SEATREG_PAYMENT_LOG_INFO);

				return false;
			case 'PAYMENT.CAPTURE.REVERSED':
				$this->changePaymentStatus(SEATREG_PAYMENT_REVERSED);
				$this->changeBookingStatus(SEATREG_BOOKING_DEFAULT);
				seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to 0 state by the system (PayPal payment reversed)', false);
				$this->log(esc_html__('Payment was reversed', 'seatreg'), SEATREG_PAYMENT_LOG_INFO);

				return false;
		}

		return false;
	}

	/**
	 *
	 * Capture an order that the buyer approved but never returned to the site to complete
	 *
	 */
	private function captureApprovedOrder() {
		if( SeatregPaymentRepository::getProcessedPaymentsByBookingId($this->_bookingId) ) {
			return;
		}

		$orderId = isset($this->_event->resource->id) ? $this->_event->resource->id : null;

		if( !$orderId ) {
			$this->log(esc_html__('PayPal order id is missing from the approved order event', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);

			return;
		}

		//Only an order that is still approved needs capturing. Capturing again would not take money
		//twice, but PayPal answers a repeated capture with the response of the first one, so there
		//would be no way to tell the two apart.
		$order = SeatregPayPalApiService::getOrder($orderId, $this->_clientId, $this->_clientSecret, $this->_sandbox);

		if( $order->success && isset($order->body->status) && $order->body->status !== 'APPROVED' ) {
			return;
		}

		$response = SeatregPayPalApiService::captureOrder(
			$orderId,
			$this->_clientId,
			$this->_clientSecret,
			$this->_sandbox,
			$this->_bookingId
		);

		if( $response->success ) {
			$this->log(esc_html__('PayPal order was captured', 'seatreg'), SEATREG_PAYMENT_LOG_INFO);

			return;
		}

		if( SeatregPayPalApiService::hasIssue($response->body, 'ORDER_ALREADY_CAPTURED') ) {
			//The booker returned to the site and the order was captured there already. Logging it
			//here would show two captures for one payment.
			return;
		}

		/* translators: %s: error message */
		$this->log(sprintf(esc_html__('Could not capture PayPal order. %s', 'seatreg'), $response->error), SEATREG_PAYMENT_LOG_ERROR);
	}

	/**
	 *
	 * Check that the certificate url points to PayPal
	 * @param string $certUrl Value of the PAYPAL-CERT-URL header
	 * @return boolean
	 *
	 */
	private static function isPayPalCertUrl($certUrl) {
		$parsedUrl = wp_parse_url($certUrl);

		if( empty($parsedUrl['scheme']) || $parsedUrl['scheme'] !== 'https' || empty($parsedUrl['host']) ) {
			return false;
		}

		$host = strtolower($parsedUrl['host']);

		return $host === 'paypal.com' || substr($host, -11) === '.paypal.com';
	}
}
