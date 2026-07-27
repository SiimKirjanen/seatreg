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
     * Returns number of enabled stripe API key usages
     *
     * @param string $stripeAPIKey The Stripe API key
     * @return number
     *
     */
    public static function getActiveStripeKeyUsage($stripeAPIKey) {
        global $wpdb;
        global $seatreg_db_table_names;

        return (int)$wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $seatreg_db_table_names->table_seatreg_options
			WHERE stripe_api_key= %s
            AND stripe_payments = 1",
			$stripeAPIKey
		) );
    }

    /**
     *
     * Returns stripe webhook secret
     *
     * @param string $stripeAPIKey The Stripe API key
     * @return string
     *
     */
    public static function getActiveStripeWebhookSecret($stripeAPIKey) {
        global $wpdb;
        global $seatreg_db_table_names;

        $results = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_options
			WHERE stripe_api_key = %s
            AND stripe_payments = 1
            AND stripe_webhook_secret IS NOT NULL",
			$stripeAPIKey
		) );

        return $results->stripe_webhook_secret;
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