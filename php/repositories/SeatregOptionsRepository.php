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
			WHERE registration_code = (SELECT registration_code FROM $seatreg_db_table_names->table_seatreg_bookings WHERE conf_code = %s LIMIT 1)",
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
}