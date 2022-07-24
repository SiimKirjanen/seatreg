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
    die('Stripe payments not turned on');
}

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

\Stripe\Stripe::setApiKey($bookingData->stripe_api_key);

header('Content-Type: application/json');

$YOUR_DOMAIN = get_site_url();

$checkout_session = \Stripe\Checkout\Session::create([
  'line_items' => [[
    'price_data' => [
      'currency' => $bookingData->stripe_currency_code,
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
  'success_url' => $YOUR_DOMAIN . '?seatreg=payment-return&id=' . $bookingId,
  'cancel_url' => $YOUR_DOMAIN . '?seatreg=booking-status&registration=' . $bookingData->registration_code . '&id=' . $bookingId,
  'payment_intent_data' => [
    'metadata' => [
      'booking_id' => $bookingId
    ]
  ]
]);

header("HTTP/1.1 303 See Other");
header("Location: " . $checkout_session->url);

