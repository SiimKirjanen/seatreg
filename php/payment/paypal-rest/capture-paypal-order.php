<?php
	//===========
	/* Capture the PayPal order the buyer just approved and send them to the return to merchant page.
	   The payment itself is recorded by the PAYMENT.CAPTURE.COMPLETED webhook. */
	//===========

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

if( empty($_GET['id']) ) {
    exit('Missing data');
}

$capturedBookingId = sanitize_text_field($_GET['id']);
$capturedBookingData = SeatregBookingRepository::getDataRelatedToBooking($capturedBookingId);

/**
 *
 * Capture an approved PayPal order
 *
 * @param string $bookingId The booking id
 * @param string $orderId The PayPal order id
 * @param object $bookingData Data related to the booking
 *
 */
function seatreg_capture_paypal_order($bookingId, $orderId, $bookingData) {
    if( SeatregPaymentRepository::getProcessedPaymentsByBookingId($bookingId) ) {
        //The webhook already recorded this payment
        return;
    }

    $clientSecret = SeatregEncryptionService::decryptValue($bookingData->paypal_client_secret);

    if( $clientSecret === null ) {
        SeatregPaymentLogService::log($bookingId, esc_html__('The saved PayPal client secret can not be read. Please enter it again in the registration settings.', 'seatreg'), SEATREG_PAYMENT_LOG_ERROR);

        return;
    }

    $sandbox = $bookingData->paypal_rest_sandbox_mode === '1';
    $response = SeatregPayPalApiService::captureOrder(
        $orderId,
        $bookingData->paypal_client_id,
        $clientSecret,
        $sandbox,
        $bookingId
    );

    if( $response->success ) {
        SeatregPaymentLogService::log($bookingId, esc_html__('PayPal order was captured', 'seatreg'), SEATREG_PAYMENT_LOG_INFO);
    }else if( !SeatregPayPalApiService::hasIssue($response->body, 'ORDER_ALREADY_CAPTURED') ) {
        /* translators: %s: error message */
        SeatregPaymentLogService::log($bookingId, sprintf(esc_html__('Could not capture PayPal order. %s', 'seatreg'), $response->error), SEATREG_PAYMENT_LOG_ERROR);

        return;
    }

    //Already captured means the approved order webhook got there first. Either way the order has to be this booking's
    $order = SeatregPayPalApiService::getOrder($orderId, $bookingData->paypal_client_id, $clientSecret, $sandbox);

    if( $order->success && isset($order->body->status, $order->body->purchase_units[0]->custom_id) && $order->body->status === 'COMPLETED' && $order->body->purchase_units[0]->custom_id === $bookingId ) {
        SeatregPaymentService::insertProcessingPayment($bookingId);
    }
}

if( SeatregPaymentRepository::isPayPalRestUsable($capturedBookingData) && !empty($_GET['token']) ) {
    seatreg_capture_paypal_order( $capturedBookingId, sanitize_text_field($_GET['token']), $capturedBookingData );
}

wp_redirect( SEATREG_PAYPAL_RETURN_URL . '&id=' . urlencode($capturedBookingId) );

exit();
