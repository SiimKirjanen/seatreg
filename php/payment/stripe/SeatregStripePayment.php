<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/payment/SeatregPaymentBase.php' );
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

class SeatregStripePayment extends SeatregPaymentBase {
    private $_stripeApiKey;
    private $_stripeWebhookSecret;
    private $_event;
    private $_webhookData;
    
    public function __construct($currency, $price, $bookingId, $setBookingConfirmed, $registrationCode, $stripeApiKey, $stripeWebhookSecret) {
        parent::__construct($currency, $price, $bookingId, $setBookingConfirmed, $registrationCode, 'Stripe');

        $this->_stripeAPiKey = $stripeApiKey;
        $this->_stripeWebhookSecret = $stripeWebhookSecret;

        $payload = @file_get_contents('php://input');
        $this->_webhookData = json_decode($payload)->data->object;
    }

    public function run() {
        if($this->webhookSignatureCheck()) {
            if($this->statusCheck()) {
                if($this->currencyAndAmountCheck($this->_webhookData->currency , $this->_webhookData->amount / 100)) {
                    if($this->paymentDoneCheck()) {
                        $this->insertPayment($this->_webhookData->payment_intent);
                        http_response_code(200);
                    }
                }
            }
        }
    }

    private function webhookSignatureCheck() {
        $payload = @file_get_contents('php://input');
        \Stripe\Stripe::setApiKey($this->_stripeApiKey);
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $endpoint_secret = $this->_stripeWebhookSecret;

        try {
            SeatregPaymentLogService::log($this->_bookingId, esc_html__('Starting to verify Stripe webhook signature', 'seatreg'));
            $this->_event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            SeatregPaymentLogService::log($this->_bookingId, esc_html__('Stripe webhook signature has invalid payload', 'seatreg'), SEATREG_PAYMENT_VALIDATION_FAILED);
            http_response_code(400);
            exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            SeatregPaymentLogService::log($this->_bookingId, esc_html__('Stripe webhook signature is invalid', 'seatreg'), SEATREG_PAYMENT_VALIDATION_FAILED);
            http_response_code(400);
            exit();
        }
        
        SeatregPaymentLogService::log($this->_bookingId, esc_html__('Stripe webhook signature verified', 'seatreg'));

        return true;
    }
       
    private function statusCheck() {
        if( $this->_event->type === 'charge.succeeded') {

            return true;
        }else if ( $this->_event->type === 'charge.failed' ) {
            $this->changePaymentStatus(SEATREG_PAYMENT_ERROR);
			$this->changeBookingStatus(SEATREG_BOOKING_DEFAULT);
			seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to 0 state by the system (Stripe payment error)', false);
			$this->log(esc_html__('Payment failed', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);

            return false;
        }else if( $this->_event->type === 'charge.refunded' ) {
            $this->changePaymentStatus(SEATREG_PAYMENT_REFUNDED);
			$this->changeBookingStatus(SEATREG_BOOKING_DEFAULT);
			seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to 0 state by the system (Stripe payment refunded)', false);
			$this->log(esc_html__('Payment was refunded', 'seatreg'), SEATREG_PAYMENT_LOG_INFO);

            return false;
        }

        return false;
    } 
}