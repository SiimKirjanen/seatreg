<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/payment/stripe/SeatregStripePayment.php' );

$payload = @file_get_contents('php://input');
$data = json_decode($payload)->data->object;
$bookingId = sanitize_text_field($data->metadata->booking_id);

if( !SeatregBookingRepository::getBookingsById($bookingId) ) {
    exit('Booking not found'); 
}

$bookingData = SeatregBookingRepository::getDataRelatedToBooking($bookingId);
$bookingTotalCost = SeatregBookingService::getBookingTotalCost($bookingId, $bookingData->registration_layout);
$stripePayment = new SeatregStripePayment(
    $bookingData->paypal_currency_code,
    $bookingTotalCost,
    $bookingId,
    $bookingData->payment_completed_set_booking_confirmed,
    $bookingData->registration_code,
    $bookingData->stripe_api_key,
    $bookingData->stripe_webhook_secret
);
$stripePayment->run();

header("HTTP/1.1 200 OK");