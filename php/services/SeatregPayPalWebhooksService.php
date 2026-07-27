<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregPayPalWebhooksService {

    /**
     *
     * Webhook events the plugin needs to keep booking payments up to date
     *
     */
    private static function eventTypes() {
        return array(
            array('name' => 'CHECKOUT.ORDER.APPROVED'),
            array('name' => 'PAYMENT.CAPTURE.COMPLETED'),
            array('name' => 'PAYMENT.CAPTURE.DENIED'),
            array('name' => 'PAYMENT.CAPTURE.REFUNDED'),
            array('name' => 'PAYMENT.CAPTURE.REVERSED')
        );
    }

    /**
     *
     * Create a PayPal webhook to get payment change notifications
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret
     * @param bool $sandbox Use the sandbox environment
     * @return object { success, webhookId, error }
     *
     */
    public static function createPayPalWebhook($clientId, $clientSecret, $sandbox) {
        $response = SeatregPayPalApiService::request(
            'POST',
            '/v1/notifications/webhooks',
            array(
                'url' => SEATREG_PAYPAL_WEBHOOK_CALLBACK_URL,
                'event_types' => self::eventTypes()
            ),
            $clientId,
            $clientSecret,
            $sandbox
        );

        if( !$response->success || empty($response->body->id) ) {
            return (object)[
                'success' => false,
                'webhookId' => null,
                'error' => $response->error
            ];
        }

        return (object)[
            'success' => true,
            'webhookId' => $response->body->id,
            'error' => null
        ];
    }

    /**
     *
     * Get all webhooks created for the PayPal app
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret
     * @param bool $sandbox Use the sandbox environment
     * @return array
     *
     */
    public static function getPayPalWebhooks($clientId, $clientSecret, $sandbox) {
        $response = SeatregPayPalApiService::request(
            'GET',
            '/v1/notifications/webhooks',
            null,
            $clientId,
            $clientSecret,
            $sandbox
        );

        if( !$response->success || empty($response->body->webhooks) ) {
            return array();
        }

        return $response->body->webhooks;
    }

    /**
     *
     * Get the id of the webhook created for the current site (SEATREG_PAYMENT_CALLBACK_URL).
     * PayPal webhooks have no description field, so the callback URL is the only thing to match on.
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret
     * @param bool $sandbox Use the sandbox environment
     * @return string|null
     *
     */
    public static function getPayPalWebhookIdForCurrentSite($clientId, $clientSecret, $sandbox) {
        $webhooks = self::getPayPalWebhooks($clientId, $clientSecret, $sandbox);

        foreach($webhooks as $webhook) {
            if( isset($webhook->url) && strpos($webhook->url, SEATREG_PAYPAL_WEBHOOK_CALLBACK_URL) !== false ) {
                return $webhook->id;
            }
        }

        return null;
    }

    /**
     *
     * Remove the webhook created for the current site
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret
     * @param bool $sandbox Use the sandbox environment
     * @return boolean
     *
     */
    public static function removePayPalWebhook($clientId, $clientSecret, $sandbox) {
        $webhookId = self::getPayPalWebhookIdForCurrentSite($clientId, $clientSecret, $sandbox);

        if( !$webhookId ) {
            return false;
        }

        $response = SeatregPayPalApiService::request(
            'DELETE',
            '/v1/notifications/webhooks/' . rawurlencode($webhookId),
            null,
            $clientId,
            $clientSecret,
            $sandbox
        );

        return $response->success;
    }

    /**
     *
     * Check that the PayPal webhook this site needs really exists in PayPal and is set up correctly.
     * Used by the "Check webhook" button in the registration settings.
     *
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret, in plain text
     * @param bool $sandbox Use the sandbox environment
     * @param string $storedWebhookId The webhook id saved for the registration
     * @return object { ok, checks }
     *
     */
    public static function checkWebhookStatus($clientId, $clientSecret, $sandbox, $storedWebhookId) {
        $checks = array();

        //A cached token could have been made with credentials that have since changed, which would
        //make this check say the credentials work when they no longer do
        SeatregPayPalApiService::clearAccessToken($clientId, $sandbox);

        $accessToken = SeatregPayPalApiService::getAccessToken($clientId, $clientSecret, $sandbox);

        if( !$accessToken->success ) {
            $checks[] = self::check(false, esc_html__('PayPal accepts the client id and secret', 'seatreg'), $accessToken->error);

            return (object)[ 'ok' => false, 'checks' => $checks ];
        }

        $checks[] = self::check(true, esc_html__('PayPal accepts the client id and secret', 'seatreg'), null);

        $webhooks = self::getPayPalWebhooks($clientId, $clientSecret, $sandbox);
        $siteWebhook = null;

        foreach($webhooks as $webhook) {
            if( isset($webhook->url) && strpos($webhook->url, SEATREG_PAYPAL_WEBHOOK_CALLBACK_URL) !== false ) {
                $siteWebhook = $webhook;

                break;
            }
        }

        if( !$siteWebhook ) {
            $checks[] = self::check(false, esc_html__('A webhook for this site exists in PayPal', 'seatreg'), SEATREG_PAYPAL_WEBHOOK_CALLBACK_URL);

            return (object)[ 'ok' => false, 'checks' => $checks ];
        }

        $checks[] = self::check(true, esc_html__('A webhook for this site exists in PayPal', 'seatreg'), $siteWebhook->url);
        $checks[] = self::check(
            $storedWebhookId === $siteWebhook->id,
            esc_html__('The saved webhook id matches the one in PayPal', 'seatreg'),
            $storedWebhookId === $siteWebhook->id ? null : esc_html__('Save the PayPal settings again to fix this', 'seatreg')
        );

        $subscribedEvents = array();

        if( !empty($siteWebhook->event_types) ) {
            foreach($siteWebhook->event_types as $eventType) {
                $subscribedEvents[] = $eventType->name;
            }
        }

        $missingEvents = array();

        foreach(self::eventTypes() as $requiredEvent) {
            if( !in_array($requiredEvent['name'], $subscribedEvents) ) {
                $missingEvents[] = $requiredEvent['name'];
            }
        }

        $checks[] = self::check(
            empty($missingEvents),
            esc_html__('The webhook listens to all events the plugin needs', 'seatreg'),
            empty($missingEvents) ? null : implode(', ', $missingEvents)
        );

        $allOk = true;

        foreach($checks as $check) {
            if( !$check->ok ) {
                $allOk = false;
            }
        }

        return (object)[ 'ok' => $allOk, 'checks' => $checks ];
    }

    private static function check($ok, $label, $detail) {
        return (object)[
            'ok' => $ok,
            'label' => $label,
            'detail' => $detail
        ];
    }

    /**
     *
     * Create or remove the PayPal webhook after PayPal payment settings have been saved
     * @param object $oldOptions Registration options before the save
     * @param string $registrationCode The code of the registration that was saved
     * @param string $clientId PayPal REST app client id that was saved
     * @param string $clientSecret PayPal REST app client secret that was saved, in plain text
     * @param bool $sandbox Sandbox environment setting that was saved
     * @param bool $payPalOn Is the PayPal payment method turned on after the save
     *
     */
    public static function syncWebhookAfterSettingsSave($oldOptions, $registrationCode, $clientId, $clientSecret, $sandbox, $payPalOn) {
        $payPalWasOn = $oldOptions->paypal_rest_payments === '1';
        //The stored secret is encrypted, so it has to be decrypted before comparing it to the saved one
        $oldClientSecret = SeatregEncryptionService::decryptValue($oldOptions->paypal_client_secret);
        $credentialsChanged = $oldOptions->paypal_client_id !== $clientId
            || $oldClientSecret !== $clientSecret
            || $oldOptions->paypal_rest_sandbox_mode !== ($sandbox ? '1' : '0');

        if( $payPalOn && ($credentialsChanged || !$payPalWasOn || !$oldOptions->paypal_webhook_id) ) {
            if( $credentialsChanged && $oldOptions->paypal_client_id ) {
                SeatregPayPalApiService::clearAccessToken($oldOptions->paypal_client_id, $oldOptions->paypal_rest_sandbox_mode === '1');
            }

            //A webhook may already exist for this site, either from another registration or from an earlier save
            $webhookId = self::getPayPalWebhookIdForCurrentSite($clientId, $clientSecret, $sandbox);

            if( !$webhookId ) {
                $createdWebhook = self::createPayPalWebhook($clientId, $clientSecret, $sandbox);

                if( !$createdWebhook->success ) {
                    //Settings are already saved at this point, but without a webhook PayPal payments
                    //can not be completed, so tell the admin what PayPal answered.
                    wp_die( 'Settings were saved, but creating the PayPal webhook failed: ' . esc_html($createdWebhook->error) );
                }

                $webhookId = $createdWebhook->webhookId;
            }

            SeatregOptionsService::updatePayPalWebhookId($webhookId, $registrationCode);
        }else if( !$payPalOn && $payPalWasOn ) {
            //Turning off PayPal payment
            SeatregOptionsService::updatePayPalWebhookId(null, $registrationCode);
            self::removeNotUsedPayPalWebhook(
                $oldOptions->paypal_client_id,
                $oldClientSecret,
                $oldOptions->paypal_rest_sandbox_mode === '1'
            );
        }
    }

    /**
     *
     * Remove the PayPal webhook if no registration uses the client id anymore
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret
     * @param bool $sandbox Use the sandbox environment
     *
     */
    public static function removeNotUsedPayPalWebhook($clientId, $clientSecret, $sandbox) {
        $activeClientIdCount = SeatregOptionsRepository::getActivePayPalClientIdUsage($clientId);

        if( $activeClientIdCount === 0 ) {
            //Remove the webhook if not used anymore
            self::removePayPalWebhook($clientId, $clientSecret, $sandbox);
        }
    }
}
