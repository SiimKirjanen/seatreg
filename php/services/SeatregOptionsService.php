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

    /**
     *
     * Save the PayPal webhook of a registration. The URL the webhook was made for is saved with it,
     * because a webhook id alone does not tell which address PayPal sends the notifications to.
     *
     * @param string|null $payPalWebhookId The webhook id in PayPal
     * @param string|null $payPalWebhookUrl The callback URL the webhook was made for
     * @param string $registrationCode The code of the registration
     *
     */
    public static function updatePayPalWebhook($payPalWebhookId, $payPalWebhookUrl, $registrationCode) {
        global $seatreg_db_table_names;
		global $wpdb;

		return $wpdb->update(
            $seatreg_db_table_names->table_seatreg_options,
            array(
                'paypal_webhook_id' => $payPalWebhookId,
                'paypal_webhook_url' => $payPalWebhookUrl,
            ),
            array(
                'registration_code' => $registrationCode
            ),
            '%s'
        );
    }
}