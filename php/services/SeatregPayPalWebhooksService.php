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
     * Does the registration have a PayPal webhook that notifies the address this site uses now.
     * A webhook made for an address the site no longer answers on delivers nothing, so it counts as missing.
     *
     * @param object $options Registration options
     * @return boolean
     *
     */
    public static function hasWebhookForCurrentSite($options) {
        return !empty($options->paypal_webhook_id)
            && $options->paypal_webhook_url === SEATREG_PAYPAL_WEBHOOK_CALLBACK_URL;
    }

    /**
     *
     * PayPal only sends notifications to a https address, so on a http site there can be no webhook
     *
     * @return boolean
     *
     */
    public static function webhookUrlIsHttps() {
        return strpos(SEATREG_PAYPAL_WEBHOOK_CALLBACK_URL, 'https://') === 0;
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
     * Used by the "Check setup" button in the registration settings.
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

        $turningOnPayPalPaymentsDetected = $payPalOn && !$payPalWasOn;
        $payPalCredentialsChangeDetected = $payPalOn && $payPalWasOn && $credentialsChanged;
        $missingWebhookDetected = $payPalOn && !$oldOptions->paypal_webhook_id;
        $webhookMadeForAnotherUrlDetected = $payPalOn && $oldOptions->paypal_webhook_url !== SEATREG_PAYPAL_WEBHOOK_CALLBACK_URL;

        if( $turningOnPayPalPaymentsDetected || $payPalCredentialsChangeDetected || $missingWebhookDetected || $webhookMadeForAnotherUrlDetected ) {
            self::createWebhookForRegistration($oldOptions, $registrationCode, $clientId, $clientSecret, $sandbox, $credentialsChanged);
        }else if( !$payPalOn && $payPalWasOn ) {
            //Turning off PayPal payment
            self::removeWebhookForRegistration($oldOptions, $registrationCode, $oldClientSecret);
        }
    }

    /**
     *
     * Make sure the registration has a working webhook id saved for it
     * @param object $oldOptions Registration options before the save
     * @param string $registrationCode The code of the registration that was saved
     * @param string $clientId PayPal REST app client id that was saved
     * @param string $clientSecret PayPal REST app client secret that was saved, in plain text
     * @param bool $sandbox Sandbox environment setting that was saved
     * @param bool $credentialsChanged Did the client id, secret or sandbox setting change with the save
     *
     */
    private static function createWebhookForRegistration($oldOptions, $registrationCode, $clientId, $clientSecret, $sandbox, $credentialsChanged) {
        if( !self::webhookUrlIsHttps() ) {
            SeatregOptionsService::updatePayPalWebhook(null, null, $registrationCode);

            return;
        }

        if( $credentialsChanged && $oldOptions->paypal_client_id ) {
            SeatregPayPalApiService::clearAccessToken($oldOptions->paypal_client_id, $oldOptions->paypal_rest_sandbox_mode === '1');
        }

        //A webhook may already exist for this site, either from another registration or from an earlier save
        $webhookId = self::getPayPalWebhookIdForCurrentSite($clientId, $clientSecret, $sandbox);

        if( !$webhookId ) {
            $createdWebhook = self::createPayPalWebhook($clientId, $clientSecret, $sandbox);

            if( !$createdWebhook->success ) {
                error_log('SeatReg: creating the PayPal webhook for registration ' . $registrationCode . ' failed: ' . $createdWebhook->error);
                //The saved webhook id can belong to credentials that are not in use anymore, so it
                //has to be cleared. The settings page then shows that payments are paused and the
                //booker is not offered PayPal until a webhook exists.
                SeatregOptionsService::updatePayPalWebhook(null, null, $registrationCode);

                return;
            }

            $webhookId = $createdWebhook->webhookId;
        }

        SeatregOptionsService::updatePayPalWebhook($webhookId, SEATREG_PAYPAL_WEBHOOK_CALLBACK_URL, $registrationCode);
    }

    /**
     *
     * Drop the webhook id from the registration and remove the webhook from PayPal if no one else uses it
     * @param object $oldOptions Registration options before the save
     * @param string $registrationCode The code of the registration that was saved
     * @param string $oldClientSecret PayPal REST app client secret before the save, in plain text
     *
     */
    private static function removeWebhookForRegistration($oldOptions, $registrationCode, $oldClientSecret) {
        SeatregOptionsService::updatePayPalWebhook(null, null, $registrationCode);
        self::removeNotUsedPayPalWebhook(
            $oldOptions->paypal_client_id,
            $oldClientSecret,
            $oldOptions->paypal_rest_sandbox_mode === '1'
        );
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
