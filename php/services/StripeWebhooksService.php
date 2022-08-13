<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class StripeWebhooksService {

    /**
     *
     * Create Stripe webhook to get payment change notifications
     * @param string $stripeAPIKey secret Stripe API key
     * 
    */
    public static function createStripeWebhook($stripeAPIKey) {
        require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

        \Stripe\Stripe::setApiKey($stripeAPIKey);
        $webhook = \Stripe\WebhookEndpoint::create([
            'url' => SEATREG_STRIPE_WEBHOOK_CALLBACK_URL,
            'description' => SEATREG_STRIPE_WEBHOOK_DESCRIPTION,
            'enabled_events' => [
              'charge.failed',
              'charge.succeeded',
              'charge.refunded',
            ],
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
        $response = \Stripe\WebhookEndpoint::all()->jsonSerialize();

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
        $stripe = new \Stripe\StripeClient($stripeAPIKey);
        $resp = $stripe->webhookEndpoints->delete($webhookIdToDelete, []);

        //$resp->jsonSerialize();
        return true;
    }
}