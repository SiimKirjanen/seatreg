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
$couponsEnabled = SeatregCouponRepository::areCouponsEnabled($bookingData->registration_code);
$appliedCoupon = SeatregCouponRepository::getBookingAppliedCoupon($bookingId);

if ( $couponsEnabled && $appliedCoupon ) {
	$bookingTotalCost = SeatregBookingService::applyCouponDiscountToTotalCost($bookingTotalCost, $appliedCoupon);
}

$stripeApiKey = SeatregEncryptionService::decryptValue($bookingData->stripe_api_key);
$stripeWebhookSecret = SeatregEncryptionService::decryptValue($bookingData->stripe_webhook_secret);

if( $stripeApiKey === null || $stripeWebhookSecret === null ) {
    SeatregPaymentLogService::log($bookingId, esc_html__('The saved Stripe credentials can not be read. Please enter the API key again in the registration settings.', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);
    //Answer with an error so that Stripe sends the event again after the credentials have been fixed
    http_response_code(500);
    exit();
}

$stripePayment = new SeatregStripePayment(
    $bookingData->paypal_currency_code,
    $bookingTotalCost,
    $bookingId,
    $bookingData->payment_completed_set_booking_confirmed_stripe,
    $bookingData->registration_code,
    $stripeApiKey,
    $stripeWebhookSecret
);
$stripePayment->run();

header("HTTP/1.1 200 OK");