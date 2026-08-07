<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregEncryptStripeCredentialsMigration {
    /**
     *
     * Encrypt the Stripe credentials that were saved before they were stored encrypted.
     *
     * Only values that are not encrypted yet are touched, so running this again does nothing.
     *
     */
    public static function run() {
        if( !SeatregEncryptionService::isOpenSSLEnabled() ) {
            return;
        }

        global $wpdb;
        global $seatreg_db_table_names;

        $rows = $wpdb->get_results(
            "SELECT id, stripe_api_key, stripe_webhook_secret FROM $seatreg_db_table_names->table_seatreg_options
            WHERE ( stripe_api_key IS NOT NULL AND stripe_api_key != '' )
            OR ( stripe_webhook_secret IS NOT NULL AND stripe_webhook_secret != '' )"
        );

        try {
            foreach( $rows as $row ) {
                $newValues = array();

                if( !empty($row->stripe_api_key) && !SeatregEncryptionService::isEncryptedValue($row->stripe_api_key) ) {
                    $newValues['stripe_api_key'] = SeatregEncryptionService::encryptValue($row->stripe_api_key);
                }

                if( !empty($row->stripe_webhook_secret) && !SeatregEncryptionService::isEncryptedValue($row->stripe_webhook_secret) ) {
                    $newValues['stripe_webhook_secret'] = SeatregEncryptionService::encryptValue($row->stripe_webhook_secret);
                }

                if( !$newValues ) {
                    continue;
                }

                $updated = $wpdb->update(
                    $seatreg_db_table_names->table_seatreg_options,
                    $newValues,
                    array( 'id' => $row->id ),
                    array_fill( 0, count($newValues), '%s' ),
                    array( '%d' )
                );

                if( $updated === false ) {
                    throw new Exception('Saving the encrypted Stripe credentials of options row ' . $row->id . ' failed: ' . $wpdb->last_error);
                }
            }
        } catch (Exception $e) {
            error_log('SeatReg: encrypting the saved Stripe credentials failed: ' . $e->getMessage());
        }
    }
}
