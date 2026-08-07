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

$stripeApiKey = SeatregEncryptionService::decryptValue($bookingData->stripe_api_key);

if( $stripeApiKey === null ) {
    SeatregPaymentLogService::log($bookingId, esc_html__('The saved Stripe API key can not be read. The WordPress security keys have most likely been changed. Please enter the API key again in the settings.', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);

    wp_die( esc_html__('Could not start the Stripe payment. Please try again later.', 'seatreg') );
}

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

\Stripe\Stripe::setApiKey($stripeApiKey);
\Stripe\Stripe::setApiVersion( SEATREG_STRIPE_API_VERSION );

$unitAmount = SeatregBookingService::getBookingTotalCost($bookingId, $bookingData->registration_layout);
$couponsEnabled = SeatregCouponRepository::areCouponsEnabled($bookingData->registration_code);
$appliedCoupon = SeatregCouponRepository::getBookingAppliedCoupon($bookingId);
$currencyCode = $bookingData->paypal_currency_code;

if ( $couponsEnabled && $appliedCoupon ) {
	$unitAmount = SeatregBookingService::applyCouponDiscountToTotalCost($unitAmount, $appliedCoupon);
}

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
    /* translators: %s: Booking ID */
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

