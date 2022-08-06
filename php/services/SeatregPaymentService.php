<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregPaymentService {
    /**
     *
     * Return seat price from registration layout
     *
    */
    public static function insertProcessingPayment($bookingId) {
        global $seatreg_db_table_names;
        global $wpdb;
    
        $alreadyInserted = SeatregPaymentRepository::getPaymentByBookingId($bookingId);
    
        if( !$alreadyInserted ) {
            $wpdb->insert(
                $seatreg_db_table_names->table_seatreg_payments,
                array(
                    'booking_id' => $bookingId,
                    'payment_status' => SEATREG_PAYMENT_PROCESSING
                ),
                '%s'
            );
            self::insertPaymentLog($bookingId, 'Return to merchant link', 'ok');
        }
    }

    public static function insertOrUpdatePayment($bookingId, $status, $txnId, $paymentCurrency, $paymentTotalPrice) {
        global $seatreg_db_table_names;
        global $wpdb;

        $alreadyInserted = SeatregPaymentRepository::getPaymentByBookingId($bookingId);

        if( $alreadyInserted ) {
            return $wpdb->update( 
                $seatreg_db_table_names->table_seatreg_payments,
                array( 
                    'payment_status' => $status,
                    'payment_txn_id' => $txnId,
                    'payment_currency' => $paymentCurrency,
                    'payment_total_price' => $paymentTotalPrice
                ), 
                array(
                    'booking_id' => $bookingId
                ),
                '%s'
            );
        }else {
            return $wpdb->insert(
                $seatreg_db_table_names->table_seatreg_payments,
                array(
                    'booking_id' => $bookingId,
                    'payment_status' => $status,
                    'payment_txn_id' => $txnId,
                    'payment_currency' => $paymentCurrency,
                    'payment_total_price' => $paymentTotalPrice
                ),
                '%s'
            );
        }
    }

    /**
     *
     * Insert payment log
     *
    */
    public static function insertPaymentLog($bookingId, $logMessage, $logStatus) {
        global $seatreg_db_table_names;
        global $wpdb;

        $wpdb->insert(
            $seatreg_db_table_names->table_seatreg_payments_log,
            array(
                'booking_id' => $bookingId,
                'log_message' => $logMessage,
                'log_status' => $logStatus
            ),
            '%s'
        );
    }

    /**
     *
     * Generate PayPal HTML Form
     *
    */
    public static function generatePayPalPayNowForm($formAction, $bookingData, $amount, $returnUrl, $cancelUrl, $notifyUrl, $bookingId) {
        ?>
            <form method="post" action="<?php echo $formAction; ?>">
                <input type="hidden" name="cmd" value="_xclick" />
                <input type="hidden" name="business" value="<?php echo esc_html($bookingData->paypal_business_email); ?>" />
                <input type="hidden" name="item_name" value="<?php echo esc_html($bookingData->registration_name) . " booking " . $bookingId; ?>" />
                <input type="hidden" name="notify_url" value="<?php echo $notifyUrl; ?>" />
                <input type="hidden" name="hosted_button_id" value="<?php echo esc_html($bookingData->paypal_button_id); ?>" />
                <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                <input type="hidden" name="currency_code" value="<?php echo esc_html($bookingData->paypal_currency_code); ?>"/>
                <input type="hidden" name="no_shipping" value="1" />
                <input type='hidden' name="cancel_return" value="<?php echo $cancelUrl; ?>" />
                <input type="hidden" name="return" value="<?php echo $returnUrl; ?>" />
                <input type="hidden" name="custom" value="<?php echo $bookingId; ?>">
                <input type="image" src="<?php echo plugins_url('../img/paypal.png', dirname(__FILE__) )?>" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
            </form>
	    <?php
    }

    /**
     *
     * Generate Stripe HTML checkout Form
     *
    */
    public static function generateStripeCheckoutForm($bookingId) {
        ?>
            <form action="<?php echo get_site_url(); ?>">
                <input type="hidden" name="seatreg" value="stripe-checkout-session" />
                <input type="hidden" name="booking-id" value="<?php echo $bookingId; ?>" />
                <input type="image" src="<?php echo plugins_url('../img/stripe.png', dirname(__FILE__) )?>" border="0" name="submit" alt="Stripe" style="margin-top: -4px" />
            </form>
        <?php
    }

    /**
     *
     * Change payment status
     * @param string $status payment status
     * @param string $bookingId The UUID of the booking
     * @return (int|false) The number of rows updated, or false on error.
     * 
    */
    public static function changePaymentStatus($status, $bookingId) {
        global $seatreg_db_table_names;
		global $wpdb;

		return $wpdb->update( 
			$seatreg_db_table_names->table_seatreg_payments,
			array( 
				'payment_status' => $status,
			), 
			array(
				'booking_id' => $bookingId
			),
			'%s'
		);
    }
}