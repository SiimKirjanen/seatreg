<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class StripeWebhooksService {

    /**
     *
     * Webhook events the plugin needs to keep booking payments up to date
     *
    */
    private static function eventTypes() {
        return array(
            'charge.failed',
            'charge.succeeded',
            'charge.refunded'
        );
    }

    /**
     *
     * Does the registration have a Stripe webhook that notifies the address this site uses now.
     * A webhook made for an address the site no longer answers on delivers nothing, so it counts as missing.
     *
     * @param object $options Registration options
     * @return boolean
     *
    */
    public static function hasWebhookForCurrentSite($options) {
        return !empty($options->stripe_webhook_secret)
            && $options->stripe_webhook_url === SEATREG_STRIPE_WEBHOOK_CALLBACK_URL;
    }

    /**
     *
     * Create Stripe webhook to get payment change notifications
     * @param string $stripeAPIKey secret Stripe API key
     *
    */
    public static function createStripeWebhook($stripeAPIKey) {
        require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

        \Stripe\Stripe::setApiKey($stripeAPIKey);
        \Stripe\Stripe::setApiVersion( SEATREG_STRIPE_API_VERSION );
        $webhook = \Stripe\WebhookEndpoint::create([
            'url' => SEATREG_STRIPE_WEBHOOK_CALLBACK_URL,
            'description' => SEATREG_STRIPE_WEBHOOK_DESCRIPTION,
            'enabled_events' => self::eventTypes(),
        ]);

        return $webhook;
    }

    /**
     *
     * Get webhooks related to SeatReg plugin
     * @param string $stripeAPIKey secret Stripe API key
     *
    */
    public static function getSeatregStripeWebhooks($stripeAPIKey) {
        require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

        \Stripe\Stripe::setApiKey($stripeAPIKey);
        \Stripe\Stripe::setApiVersion( SEATREG_STRIPE_API_VERSION );
        //Stripe returns 10 endpoints without a limit, which can hide the one this site uses
        $response = \Stripe\WebhookEndpoint::all(['limit' => 100])->jsonSerialize();

        return array_filter($response['data'], function($webhook) {
            return $webhook['description'] === SEATREG_STRIPE_WEBHOOK_DESCRIPTION;
        });
    }
    /**
     *
     * Is webhook created in Stripe for current site (SEATREG_PAYMENT_CALLBACK_URL)
     * @param string $stripeAPIKey secret Stripe API key
     * 
    */
    public static function isStripeWebhookCreatedForCurrentSite($stripeAPIKey) {
        $webhooks = self::getSeatregStripeWebhooks($stripeAPIKey);    
        $currentSiteWebhooks = array_filter($webhooks, function($webhook){
            return strpos($webhook['url'], SEATREG_PAYMENT_CALLBACK_URL) !== false;
        });

        return !empty($currentSiteWebhooks);
    }

    public static function removeStripeWebhook($stripeAPIKey) {
        require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

        $webhooks = self::getSeatregStripeWebhooks($stripeAPIKey);    
        $currentSiteWebhooks = array_values( array_filter($webhooks, function($webhook){
            return strpos($webhook['url'], SEATREG_PAYMENT_CALLBACK_URL) !== false;
        }) );

        if( !$currentSiteWebhooks ) {
            return false;
        }

        $webhookIdToDelete = $currentSiteWebhooks[0]['id'];
        $stripe = new \Stripe\StripeClient([
            'api_key' => $stripeAPIKey,
            'stripe_version' => SEATREG_STRIPE_API_VERSION
        ]);
        $resp = $stripe->webhookEndpoints->delete($webhookIdToDelete, []);

        //$resp->jsonSerialize();
        return true;
    }

    /**
     *
     * Check that the Stripe webhook this site needs really exists in Stripe and is set up correctly.
     * Used by the "Check setup" button in the registration settings.
     *
     * @param string $stripeAPIKey secret Stripe API key, in plain text
     * @param string|null $storedWebhookSecret The webhook signing secret saved for the registration, in plain text
     * @return object { ok, checks }
     *
    */
    public static function checkWebhookStatus($stripeAPIKey, $storedWebhookSecret) {
        $checks = array();

        try {
            $webhooks = self::getSeatregStripeWebhooks($stripeAPIKey);
        } catch (Exception $e) {
            $checks[] = self::check(false, esc_html__('Stripe accepts the API key', 'seatreg'), $e->getMessage());

            return (object)[ 'ok' => false, 'checks' => $checks ];
        }

        $checks[] = self::check(true, esc_html__('Stripe accepts the API key', 'seatreg'), null);

        $siteWebhook = null;

        foreach($webhooks as $webhook) {
            if( isset($webhook['url']) && strpos($webhook['url'], SEATREG_PAYMENT_CALLBACK_URL) !== false ) {
                $siteWebhook = $webhook;

                break;
            }
        }

        if( !$siteWebhook ) {
            $checks[] = self::check(false, esc_html__('A webhook for this site exists in Stripe', 'seatreg'), SEATREG_STRIPE_WEBHOOK_CALLBACK_URL);

            return (object)[ 'ok' => false, 'checks' => $checks ];
        }

        $checks[] = self::check(true, esc_html__('A webhook for this site exists in Stripe', 'seatreg'), $siteWebhook['url']);

        $webhookEnabled = isset($siteWebhook['status']) && $siteWebhook['status'] === 'enabled';
        $checks[] = self::check(
            $webhookEnabled,
            esc_html__('The webhook is enabled in Stripe', 'seatreg'),
            $webhookEnabled ? null : esc_html__('Turn the webhook back on in the Stripe dashboard', 'seatreg')
        );

        $subscribedEvents = isset($siteWebhook['enabled_events']) ? $siteWebhook['enabled_events'] : array();
        $missingEvents = array();

        //Stripe uses * for a webhook that is subscribed to every event
        if( !in_array('*', $subscribedEvents) ) {
            foreach(self::eventTypes() as $requiredEvent) {
                if( !in_array($requiredEvent, $subscribedEvents) ) {
                    $missingEvents[] = $requiredEvent;
                }
            }
        }

        $checks[] = self::check(
            empty($missingEvents),
            esc_html__('The webhook listens to all events the plugin needs', 'seatreg'),
            empty($missingEvents) ? null : implode(', ', $missingEvents)
        );

        //Stripe only gives out the signing secret when the webhook is created, so it can only be
        //checked that one is saved, not that it is the same one Stripe signs with
        $checks[] = self::check(
            !empty($storedWebhookSecret),
            esc_html__('A webhook signing secret is saved for this registration', 'seatreg'),
            !empty($storedWebhookSecret) ? null : esc_html__('Save the Stripe settings again to fix this', 'seatreg')
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
     * Remove Stripe API key webhook if not used
     * @param string $stripeAPIKey secret Stripe API key
     *
    */
    public static function removeNotUsedStripeAPiWebhook($stripeAPIKey) {
        $activeStripeKeyCount = SeatregOptionsRepository::getActiveStripeKeyUsage($stripeAPIKey);

		if( $activeStripeKeyCount === 0 ) {
			//Remove the webhook if not used anymore
            StripeWebhooksService::removeStripeWebhook($stripeAPIKey);
        }
    }

    /**
     *
     * Create or remove the Stripe webhook after Stripe payment settings have been saved
     * @param object $oldOptions Registration options before the save
     * @param string $registrationCode The code of the registration that was saved
     * @param string|null $stripeAPIKey The Stripe API key that was saved, in plain text
     * @param bool $stripeOn Is the Stripe payment method turned on after the save
     *
    */
    public static function syncWebhookAfterSettingsSave($oldOptions, $registrationCode, $stripeAPIKey, $stripeOn) {
        $stripeWasOn = $oldOptions->stripe_payments === '1';
        $oldStripeAPIKey = SeatregEncryptionService::decryptValue($oldOptions->stripe_api_key);

        $turningOnStripePaymentsDetected = $stripeOn && !$stripeWasOn;
        $stripeAPiKeyChangeDetected = $stripeOn && $stripeWasOn && $oldStripeAPIKey !== $stripeAPIKey;
        $missingWebhookDetected = $stripeOn && !self::hasWebhookForCurrentSite($oldOptions);

        if( $turningOnStripePaymentsDetected || $stripeAPiKeyChangeDetected || $missingWebhookDetected ) {
            self::createWebhookForRegistration($registrationCode, $stripeAPIKey);
        }else if( !$stripeOn && $stripeWasOn ) {
            self::removeWebhookForRegistration($registrationCode, $oldStripeAPIKey);
        }
    }

    /**
     *
     * Make sure the registration has a working webhook secret saved for it
     * @param string $registrationCode The code of the registration that was saved
     * @param string|null $stripeAPIKey The Stripe API key that was saved, in plain text
     *
    */
    private static function createWebhookForRegistration($registrationCode, $stripeAPIKey) {
        try {
            $webhookSecret = self::isStripeWebhookCreatedForCurrentSite($stripeAPIKey)
                ? SeatregOptionsRepository::getActiveStripeWebhookSecret($stripeAPIKey)
                : null;

            if( $webhookSecret === null ) {
                self::removeStripeWebhook($stripeAPIKey);

                $webhookSecret = self::createStripeWebhook($stripeAPIKey)->secret;
            }

            SeatregOptionsService::updateStripeWebhook($webhookSecret, SEATREG_STRIPE_WEBHOOK_CALLBACK_URL, $registrationCode);
        } catch (Exception $e) {
            error_log('SeatReg: creating the Stripe webhook for registration ' . $registrationCode . ' failed: ' . $e->getMessage());
            SeatregOptionsService::updateStripeWebhook(null, null, $registrationCode);
        }
    }

    /**
     *
     * Forget the webhook of a registration and remove it from Stripe when no one else uses it
     * @param string $registrationCode The code of the registration that was saved
     * @param string|null $stripeAPIKey The Stripe API key the webhook was made with, in plain text
     *
    */
    private static function removeWebhookForRegistration($registrationCode, $stripeAPIKey) {
        SeatregOptionsService::updateStripeWebhook(null, null, $registrationCode);

        if( !$stripeAPIKey ) {
            return;
        }

        try {
            self::removeNotUsedStripeAPiWebhook($stripeAPIKey);
        } catch (Exception $e) {
            error_log('SeatReg: removing the Stripe webhook of registration ' . $registrationCode . ' failed: ' . $e->getMessage());
        }
    }
}