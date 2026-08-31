<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregBackfillStripeWebhookUrlMigration {
    /**
     *
     * Record the callback URL of Stripe webhooks that were saved before the URL was kept.
     *
     * Registrations that already have a signing secret got it from a webhook this site made, but
     * there is no record of the address it was made for. The address the site uses now is the only
     * sensible guess, and it keeps those registrations working exactly as they did before. A webhook
     * that really was made for an older address is reported by the Setup check button.
     *
     * Rows that already have a URL are left alone, so running this again does nothing.
     *
     */
    public static function run() {
        global $wpdb;
        global $seatreg_db_table_names;

        $updated = $wpdb->query( $wpdb->prepare(
            "UPDATE $seatreg_db_table_names->table_seatreg_options
            SET stripe_webhook_url = %s
            WHERE stripe_webhook_secret IS NOT NULL
            AND stripe_webhook_secret != ''
            AND stripe_webhook_url IS NULL",
            SEATREG_STRIPE_WEBHOOK_CALLBACK_URL
        ) );

        if( $updated === false ) {
            error_log('SeatReg: saving the callback URL of the existing Stripe webhooks failed: ' . $wpdb->last_error);
        }
    }
}
