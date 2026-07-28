<?php
	//===========
	/* Create a PayPal order and send the buyer to PayPal to approve it */
	//===========

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

if( empty( $_GET['booking-id'] ) ) {
    die('Booking ID missing');
}

$bookingId = sanitize_text_field( $_GET['booking-id'] );
$bookingData = SeatregBookingRepository::getDataRelatedToBooking( $bookingId );


if( !SeatregPaymentRepository::isPayPalRestUsable($bookingData) ) {
    die('PayPal payment is not turned on');
}

$totalCost = SeatregBookingService::getBookingTotalCost($bookingId, $bookingData->registration_layout);
$couponsEnabled = SeatregCouponRepository::areCouponsEnabled($bookingData->registration_code);
$appliedCoupon = SeatregCouponRepository::getBookingAppliedCoupon($bookingId);
$currencyCode = $bookingData->paypal_currency_code;
$sandbox = $bookingData->paypal_rest_sandbox_mode === '1';
$clientSecret = SeatregEncryptionService::decryptValue($bookingData->paypal_client_secret);

if( $clientSecret === null ) {
    SeatregPaymentLogService::log($bookingId, esc_html__('The saved PayPal client secret can not be read. Please enter it again in the registration settings.', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);

    wp_die( esc_html__('Could not start the PayPal payment. Please try again later.', 'seatreg') );
}

if ( $couponsEnabled && $appliedCoupon ) {
	$totalCost = SeatregBookingService::applyCouponDiscountToTotalCost($totalCost, $appliedCoupon);
}

$response = SeatregPayPalApiService::createOrder(
    array(
        'intent' => 'CAPTURE',
        'purchase_units' => array(
            array(
                'custom_id' => $bookingId,
                /* translators: %s: Booking ID */
                'description' => sprintf( __('Booking %s', 'seatreg'), $bookingId ),
                'amount' => array(
                    'currency_code' => $currencyCode,
                    'value' => SeatregPayPalApiService::formatAmount($totalCost, $currencyCode)
                )
            )
        ),
        'payment_source' => array(
            'paypal' => array(
                'experience_context' => array(
                    'brand_name' => $bookingData->registration_name,
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING',
                    'return_url' => SEATREG_PAYPAL_REST_RETURN_URL . '&id=' . $bookingId,
                    'cancel_url' => SEATREG_PAYPAL_REST_CANCEL_URL . '&registration=' . $bookingData->registration_code . '&id=' . $bookingId
                )
            )
        )
    ),
    $bookingData->paypal_client_id,
    $clientSecret,
    $sandbox
);

if( !$response->success ) {
    /* translators: %s: error message */
    SeatregPaymentLogService::log($bookingId, sprintf(esc_html__('Could not create PayPal order. %s', 'seatreg'), $response->error), SEATREG_PAYMENT_LOG_ERROR);

    wp_die( esc_html__('Could not start the PayPal payment. Please try again later.', 'seatreg') );
}

//PayPal returns the page to send the buyer to as a "payer-action" link. Older responses use "approve".
$approveUrl = SeatregPayPalApiService::findLink($response->body->links, 'payer-action');

if( !$approveUrl ) {
    $approveUrl = SeatregPayPalApiService::findLink($response->body->links, 'approve');
}

if( !$approveUrl ) {
    SeatregPaymentLogService::log($bookingId, esc_html__('PayPal did not return an approval link', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);

    wp_die( esc_html__('Could not start the PayPal payment. Please try again later.', 'seatreg') );
}

header("HTTP/1.1 303 See Other");
header("Location: " . $approveUrl);
