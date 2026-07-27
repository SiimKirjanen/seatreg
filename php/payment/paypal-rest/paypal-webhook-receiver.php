<?php
	//===========
	/* PayPal REST API webhook receiver */
	//===========

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/payment/paypal-rest/SeatregPayPalPayment.php' );

/**
 *
 * Find the id of the capture a PayPal webhook event belongs to
 *
 * @param object $resource The resource of the webhook event
 * @return string|null
 *
 */
function seatreg_get_paypal_webhook_capture_id($resource) {
    if( empty($resource->links) || !is_array($resource->links) ) {
        return null;
    }

    foreach($resource->links as $link) {
        //A refund links back to the capture it was made from with the "up" relation
        if( isset($link->rel) && $link->rel === 'up' && isset($link->href) && strpos($link->href, '/captures/') !== false ) {
            $hrefParts = explode('/', untrailingslashit($link->href));

            return sanitize_text_field( end($hrefParts) );
        }
    }

    return null;
}

/**
 *
 * Find the booking id from a PayPal webhook event. The booking id is sent to PayPal as the
 * purchase unit custom_id, which PayPal echoes back on order, capture and refund resources.
 *
 * @param object $event The decoded webhook event
 * @return string|null
 *
 */
function seatreg_get_paypal_webhook_booking_id($event) {
    if( empty($event->resource) ) {
        return null;
    }

    $resource = $event->resource;

    //Capture and refund resources
    if( !empty($resource->custom_id) ) {
        return sanitize_text_field($resource->custom_id);
    }

    //Order resources (CHECKOUT.ORDER.APPROVED)
    if( !empty($resource->purchase_units[0]->custom_id) ) {
        return sanitize_text_field($resource->purchase_units[0]->custom_id);
    }

    //Refunds do not always carry custom_id. Find the booking through the captured payment instead.
    $captureId = seatreg_get_paypal_webhook_capture_id($resource);

    if( $captureId ) {
        $payment = SeatregPaymentRepository::getPaymentByTxnId($captureId);

        if( $payment ) {
            return $payment->booking_id;
        }
    }

    return null;
}

$rawPayload = @file_get_contents('php://input');
$event = json_decode($rawPayload);

if( !$event ) {
    exit('Missing data');
}

$bookingId = seatreg_get_paypal_webhook_booking_id($event);

if( !$bookingId ) {
    exit('Booking not found');
}

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

$clientSecret = SeatregEncryptionService::decryptValue($bookingData->paypal_client_secret);

if( $clientSecret === null ) {
    SeatregPaymentLogService::log($bookingId, esc_html__('The saved PayPal client secret can not be read. Please enter it again in the registration settings.', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);
    //Answer with an error so that PayPal sends the event again after the secret has been fixed
    http_response_code(500);
    exit();
}

$payPalPayment = new SeatregPayPalPayment(
    $bookingData->paypal_currency_code,
    $bookingTotalCost,
    $bookingId,
    $bookingData->payment_completed_set_booking_confirmed_paypal_rest,
    $bookingData->registration_code,
    $bookingData->paypal_client_id,
    $clientSecret,
    $bookingData->paypal_rest_sandbox_mode === '1',
    $bookingData->paypal_webhook_id,
    $rawPayload
);
$payPalPayment->run();

header("HTTP/1.1 200 OK");
