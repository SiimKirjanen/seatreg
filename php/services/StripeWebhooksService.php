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
            ],
        ]);

        return $webhook;
    }

    public static function getStripeWebhooks($stripeAPIKey) {
        require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

        \Stripe\Stripe::setApiKey($stripeAPIKey);
        $response = \Stripe\WebhookEndpoint::all()->jsonSerialize();

        return $response['data'];     
    }

    public static function isStripeWebhookCreated($stripeAPIKey) {
        $webhooks = self::getStripeWebhooks($stripeAPIKey);    
        $seatregWebhooks = array_filter($webhooks, function($webhook){
            return $webhook['description'] === SEATREG_STRIPE_WEBHOOK_DESCRIPTION;
        });

        return !empty($seatregWebhooks);
    }

    public static function removeStripeWebhook($stripeAPIKey) {
        require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/stripe-php/init.php' );

        $webhooks = self::getStripeWebhooks($stripeAPIKey);    
        $seatregWebhooks = array_filter($webhooks, function($webhook){
            return $webhook['description'] === SEATREG_STRIPE_WEBHOOK_DESCRIPTION;
        });

        $webhookIdToDelete = $seatregWebhooks[0]['id'];

        $stripe = new \Stripe\StripeClient($stripeAPIKey);

        $resp = $stripe->webhookEndpoints->delete($webhookIdToDelete, []);

        return $resp->jsonSerialize();
    }

}