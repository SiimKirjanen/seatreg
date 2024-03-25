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
\Stripe\Stripe::setApiVersion( SEATREG_STRIPE_API_VERSION );

$unitAmount = SeatregBookingService::getBookingTotalCost($bookingId, $bookingData->registration_layout);
$currencyCode = $bookingData->paypal_currency_code;

if( !in_array($currencyCode, SEATREG_STRIPE_ZERO_DECIMAL_CURRENCIES) ) {
  $unitAmount = $unitAmount * 100;
}

$checkout_session = \Stripe\Checkout\Session::create([
  'line_items' => [[
    'price_data' => [
      'currency' => $currencyCode,
      'unit_amount' => $unitAmount,
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
  'allow_promotion_codes' => (bool)$bookingData->stripe_allow_promotion_codes,
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

