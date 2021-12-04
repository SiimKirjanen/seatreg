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
            self::insertPaymentLog($bookingId, 'PayPal return to merchant', 'ok');
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
                <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
            </form>
	    <?php
    }
}