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
        $currentSiteWebhooks = array_filter($webhooks, function($webhook){
            return strpos($webhook['url'], SEATREG_PAYMENT_CALLBACK_URL) !== false;
        });

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
}