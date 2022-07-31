<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

$payload = @file_get_contents('php://input');
$data = json_decode($payload)->data->object;
$bookingId = sanitize_text_field($data->metadata->booking_id);
$bookingCurrency = $data->currency;
$bookingAmount = $data->amount / 100;
$bookingData = SeatregBookingRepository::getDataRelatedToBooking( $bookingId );

\Stripe\Stripe::setApiKey($bookingData->stripe_api_key);
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$endpoint_secret = $bookingData->stripe_webhook_secret;
$event = null;

try {
    SeatregPaymentLogService::log($bookingId, esc_html__('Starting to verify Stripe webhook signature', 'seatreg'));
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    SeatregPaymentLogService::log($bookingId, esc_html__('Stripe webhook signature has invalid payload', 'seatreg'));
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    SeatregPaymentLogService::log($bookingId, esc_html__('Stripe webhook signature is invalid', 'seatreg'));
    http_response_code(400);
    exit();
}

SeatregPaymentLogService::log($bookingId, esc_html__('Stripe webhook signature verified', 'seatreg'));

// Handle the event
switch ($event->type) {
    case 'charge.succeeded':
        SeatregPaymentService::insertOrUpdatePayment($bookingId, SEATREG_PAYMENT_COMPLETED, $data->id, $bookingCurrency, $bookingAmount);
        SeatregPaymentLogService::log($bookingId, sprintf(esc_html__('Payment for %s is completed', 'seatreg'), "$this->_price $this->_currency"));

        if($bookingData->payment_complated_set_booking_confirmed_stripe === '1') {
            SeatregBookingService::changeBookingStatus(SEATREG_BOOKING_APPROVED, $bookingId);
			seatreg_add_activity_log('booking', $bookingId, 'Booking set to approved state by the system (Stripe)', false);
			seatreg_send_approved_booking_email($bookingId, $bookingData->registrationCode, $bookingData->approved_booking_email_template);
        }
        
        break;
    case 'charge.failed':
        $paymentMethod = $event->data->object; // contains a \Stripe\PaymentMethod
        break;
    // ... handle other event types
    default:
        echo 'Received unknown event type ' . $event->type;
}

http_response_code(200);