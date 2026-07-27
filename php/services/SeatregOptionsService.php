<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}


class SeatregOptionsService {
    public static function updateStripeWebhookSecret($stripeWebhookSecret, $registrationCode) {
        global $seatreg_db_table_names;
		global $wpdb;

		return $wpdb->update( 
            $seatreg_db_table_names->table_seatreg_options,
            array( 
                'stripe_webhook_secret' => $stripeWebhookSecret,
            ), 
            array(
                'registration_code' => $registrationCode
            ),
            '%s'
        );
    }

    public static function updatePayPalWebhookId($payPalWebhookId, $registrationCode) {
        global $seatreg_db_table_names;
		global $wpdb;

		return $wpdb->update(
            $seatreg_db_table_names->table_seatreg_options,
            array(
                'paypal_webhook_id' => $payPalWebhookId,
            ),
            array(
                'registration_code' => $registrationCode
            ),
            '%s'
        );
    }
}