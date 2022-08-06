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
                        $this->insertPayment($this->_webhookData->id);
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
            SeatregPaymentLogService::log($this->_bookingId, esc_html__('Stripe webhook signature has invalid payload', 'seatreg'));
            http_response_code(400);
            exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            SeatregPaymentLogService::log($this->_bookingId, esc_html__('Stripe webhook signature is invalid', 'seatreg'));
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
            return false;
        }

        return false;
    } 
}