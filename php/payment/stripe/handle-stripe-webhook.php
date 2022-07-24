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
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Handle the event
switch ($event->type) {
    case 'charge.succeeded':
        $paymentIntent = $event->data->object; // contains a \Stripe\PaymentIntent
        break;
    case 'charge.failed':
        $paymentMethod = $event->data->object; // contains a \Stripe\PaymentMethod
        break;
    // ... handle other event types
    default:
        echo 'Received unknown event type ' . $event->type;
}

http_response_code(200);