<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit(); 
}

if( empty( $_GET['booking-id'] ) ) {
    die('Booking ID missing');
}

$bookingId = sanitize_text_field( $_GET['booking-id'] );
$bookingData = SeatregBookingRepository::getDataRelatedToBooking( $bookingId );

if( $bookingData->stripe_payments !== '1' ) {
    die('Stripe payment is not turned on');
}

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

\Stripe\Stripe::setApiKey($bookingData->stripe_api_key);

$checkout_session = \Stripe\Checkout\Session::create([
  'line_items' => [[
    'price_data' => [
      'currency' => $bookingData->paypal_currency_code,
      'unit_amount' => SeatregBookingService::getBookingTotalCost($bookingId, $bookingData->registration_layout) * 100,
      'product_data' => [
        'name' => $bookingData->registration_name,
        'metadata' => [
          'booking_id' => $bookingId
        ]
      ],
    ],
    'description' => sprintf( __('Booking %s', 'seatreg'),  $bookingId),
    'quantity' => 1,
  ]],
  'mode' => 'payment',
  'success_url' => SEATREG_STRIPE_WEBHOOK_SUCCESS_URL . '&id=' . $bookingId,
  'cancel_url' => SEATREG_STRIPE_WEBHOOK_CANCEL_URL . '&registration=' . $bookingData->registration_code . '&id=' . $bookingId,
  'payment_intent_data' => [
    'metadata' => [
      'booking_id' => $bookingId
    ]
  ]
]);

header('Content-Type: application/json');
header("HTTP/1.1 303 See Other");
header("Location: " . $checkout_session->url);

