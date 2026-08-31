<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregOptionsRepository {
    /**
     *
     * Return options by registration code
     *
     * @param string $registrationCode The code of registration
     *
     */
    public static function getOptionsByRegistrationCode($registrationCode) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_options
			WHERE registration_code = %s",
			$registrationCode
		) );
    }
     /**
     *
     * Return options by confirmation code
     *
     * @param string $confirmationCode The code for confirming booking
     *
     */
    public static function getOptionsByConfirmationCode($confirmationCode) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_options
			WHERE registration_code = (SELECT registration_code FROM $seatreg_db_table_names->table_seatreg_bookings WHERE conf_code = %s AND is_deleted = 0 LIMIT 1)",
			$confirmationCode
		) );
    }

    /**
     *
     * Returns the options rows that have Stripe payments turned on with the given API key.
     *
     * The API key is stored encrypted with a random IV, so the same key does not give the same
     * stored value twice and can not be matched in SQL. The rows are decrypted and compared here
     * instead. There is one options row per registration, so the amount of rows stays small.
     *
     * @param string $stripeAPIKey The Stripe API key in plain text
     * @return array
     *
     */
    private static function getActiveStripeRows($stripeAPIKey) {
        global $wpdb;
        global $seatreg_db_table_names;

        $rows = $wpdb->get_results(
			"SELECT stripe_api_key, stripe_webhook_secret FROM $seatreg_db_table_names->table_seatreg_options
			WHERE stripe_payments = 1
            AND stripe_api_key IS NOT NULL"
		);

        return array_values( array_filter($rows, function($row) use ($stripeAPIKey) {
            return SeatregEncryptionService::decryptValue($row->stripe_api_key) === $stripeAPIKey;
        }) );
    }

    /**
     *
     * Returns number of enabled stripe API key usages
     *
     * @param string $stripeAPIKey The Stripe API key in plain text
     * @return number
     *
     */
    public static function getActiveStripeKeyUsage($stripeAPIKey) {
        return count( self::getActiveStripeRows($stripeAPIKey) );
    }

    /**
     *
     * Returns stripe webhook secret
     *
     * @param string $stripeAPIKey The Stripe API key in plain text
     * @return string|null The webhook signing secret in plain text, or null when there is none saved
     *                     for this key or it can not be read
     *
     */
    public static function getActiveStripeWebhookSecret($stripeAPIKey) {
        foreach( self::getActiveStripeRows($stripeAPIKey) as $row ) {
            if( empty($row->stripe_webhook_secret) ) {
                continue;
            }

            $webhookSecret = SeatregEncryptionService::decryptValue($row->stripe_webhook_secret);

            if( $webhookSecret !== null ) {
                return $webhookSecret;
            }
        }

        return null;
    }

    /**
     *
     * Returns number of enabled PayPal client id usages
     *
     * @param string $payPalClientId The PayPal REST app client id
     * @return number
     *
     */
    public static function getActivePayPalClientIdUsage($payPalClientId) {
        global $wpdb;
        global $seatreg_db_table_names;

        return (int)$wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $seatreg_db_table_names->table_seatreg_options
			WHERE paypal_client_id = %s
            AND paypal_rest_payments = 1",
			$payPalClientId
		) );
    }

    /**
     *
     * Returns the PayPal webhook id that is already in use with the given client id
     *
     * @param string $payPalClientId The PayPal REST app client id
     * @return string|null
     *
     */
    public static function getActivePayPalWebhookId($payPalClientId) {
        global $wpdb;
        global $seatreg_db_table_names;

        return $wpdb->get_var( $wpdb->prepare(
			"SELECT paypal_webhook_id FROM $seatreg_db_table_names->table_seatreg_options
			WHERE paypal_client_id = %s
            AND paypal_rest_payments = 1
            AND paypal_webhook_id IS NOT NULL
            LIMIT 1",
			$payPalClientId
		) );
    }

    /**
     *
     * Is it allowed to generate booking PDF?
     *
     * @param string $bookings Array of bookings
     * @param object $bookingData Object of booking data
     * @return boolean
     *
     */
    public static function shouldAllowPdfGeneration($bookings, $bookingData) {
        if(!$bookings) {
            return false;
        }
    
        if( $bookings[0]->status === '1' &&  $bookingData->show_pending_booking_pdf === '0' ) {
            return false;
        }else if( $bookings[0]->status === '2' && $bookingData->show_approved_booking_pdf === '0' ) {
            return false;
        }
    
        return true;
    }
}