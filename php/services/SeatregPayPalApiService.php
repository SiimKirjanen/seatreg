<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 *
 * Minimal PayPal REST API client (OAuth2 + Orders v2 + webhook signature verification).
 * PayPal's official PHP SDK requires Composer, which this plugin does not use, so requests
 * are made with the WordPress HTTP API.
 *
 */
class SeatregPayPalApiService {

    /**
     *
     * PayPal REST API base URL
     * @param bool $sandbox Use the sandbox environment
     * @return string
     *
     */
    public static function getBaseUrl($sandbox) {
        return $sandbox ? SEATREG_PAYPAL_API_BASE_SANDBOX : SEATREG_PAYPAL_API_BASE;
    }

    /**
     *
     * Get an OAuth2 access token. Tokens are cached in a transient until shortly before they expire.
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret
     * @param bool $sandbox Use the sandbox environment
     * @return object { success, token, error }
     *
     */
    public static function getAccessToken($clientId, $clientSecret, $sandbox) {
        $transientName = SEATREG_PAYPAL_ACCESS_TOKEN_TRANSIENT_PREFIX . md5($clientId . ($sandbox ? '1' : '0'));
        $cachedToken = get_transient($transientName);

        if( $cachedToken ) {
            return (object)[
                'success' => true,
                'token' => $cachedToken,
                'error' => null
            ];
        }

        $response = wp_remote_post( self::getBaseUrl($sandbox) . '/v1/oauth2/token', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ),
            'body' => array(
                'grant_type' => 'client_credentials'
            )
        ) );

        if( is_wp_error($response) ) {
            return (object)[
                'success' => false,
                'token' => null,
                'error' => $response->get_error_message()
            ];
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode( wp_remote_retrieve_body($response) );

        if( $status < 200 || $status >= 300 || empty($body->access_token) ) {
            return (object)[
                'success' => false,
                'token' => null,
                'error' => self::describeError($status, $body)
            ];
        }

        //Keep a small safety margin so a token is never used in the second it expires
        $expiresIn = isset($body->expires_in) ? (int)$body->expires_in - 60 : 0;

        if( $expiresIn > 0 ) {
            set_transient($transientName, $body->access_token, $expiresIn);
        }

        return (object)[
            'success' => true,
            'token' => $body->access_token,
            'error' => null
        ];
    }

    /**
     *
     * Remove a cached access token. Used when credentials change.
     * @param string $clientId PayPal REST app client id
     * @param bool $sandbox Use the sandbox environment
     *
     */
    public static function clearAccessToken($clientId, $sandbox) {
        delete_transient( SEATREG_PAYPAL_ACCESS_TOKEN_TRANSIENT_PREFIX . md5($clientId . ($sandbox ? '1' : '0')) );
    }

    /**
     *
     * Make an authenticated request against the PayPal REST API
     * @param string $method HTTP method
     * @param string $path API path starting with a slash
     * @param array|string|null $body Request body. Arrays are JSON encoded, strings are sent as is
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret
     * @param bool $sandbox Use the sandbox environment
     * @param array $extraHeaders Additional request headers
     * @return object { success, status, body, error }
     *
     */
    public static function request($method, $path, $body, $clientId, $clientSecret, $sandbox, $extraHeaders = array()) {
        $accessToken = self::getAccessToken($clientId, $clientSecret, $sandbox);

        if( !$accessToken->success ) {
            return (object)[
                'success' => false,
                'status' => 0,
                'body' => null,
                'error' => $accessToken->error
            ];
        }

        $headers = array_merge(array(
            'Authorization' => 'Bearer ' . $accessToken->token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ), $extraHeaders);

        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => $headers
        );

        if( $body !== null ) {
            $args['body'] = is_string($body) ? $body : wp_json_encode($body);
        }

        $response = wp_remote_request( self::getBaseUrl($sandbox) . $path, $args );

        if( is_wp_error($response) ) {
            return (object)[
                'success' => false,
                'status' => 0,
                'body' => null,
                'error' => $response->get_error_message()
            ];
        }

        $status = wp_remote_retrieve_response_code($response);
        $decodedBody = json_decode( wp_remote_retrieve_body($response) );
        $success = $status >= 200 && $status < 300;

        return (object)[
            'success' => $success,
            'status' => $status,
            'body' => $decodedBody,
            'error' => $success ? null : self::describeError($status, $decodedBody)
        ];
    }

    /**
     *
     * Create a PayPal order
     * @param array $orderData Orders v2 create order payload
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret
     * @param bool $sandbox Use the sandbox environment
     * @return object
     *
     */
    public static function createOrder($orderData, $clientId, $clientSecret, $sandbox) {
        return self::request('POST', '/v2/checkout/orders', $orderData, $clientId, $clientSecret, $sandbox);
    }

    /**
     *
     * Capture an approved PayPal order
     * @param string $orderId PayPal order id
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret
     * @param bool $sandbox Use the sandbox environment
     * @param string $requestId Value for the PayPal-Request-Id idempotency header
     * @return object
     *
     */
    public static function captureOrder($orderId, $clientId, $clientSecret, $sandbox, $requestId = null) {
        $extraHeaders = array();

        if( $requestId ) {
            $extraHeaders['PayPal-Request-Id'] = $requestId;
        }

        return self::request(
            'POST',
            '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture',
            //PayPal requires a JSON body on capture even when there is nothing to send
            '{}',
            $clientId,
            $clientSecret,
            $sandbox,
            $extraHeaders
        );
    }

    /**
     *
     * Get a PayPal order
     * @param string $orderId PayPal order id
     * @param string $clientId PayPal REST app client id
     * @param string $clientSecret PayPal REST app client secret
     * @param bool $sandbox Use the sandbox environment
     * @return object
     *
     */
    public static function getOrder($orderId, $clientId, $clientSecret, $sandbox) {
        return self::request('GET', '/v2/checkout/orders/' . rawurlencode($orderId), null, $clientId, $clientSecret, $sandbox);
    }

    /**
     *
     * Find a link with the given rel from a PayPal HATEOAS links array
     * @param array $links Links from a PayPal response
     * @param string $rel Link relation to look for
     * @return string|null
     *
     */
    public static function findLink($links, $rel) {
        if( !is_array($links) ) {
            return null;
        }

        foreach($links as $link) {
            if( isset($link->rel) && $link->rel === $rel ) {
                return $link->href;
            }
        }

        return null;
    }

    /**
     *
     * Format an amount the way PayPal expects it for the given currency
     * @param float $value The amount
     * @param string $currencyCode ISO 4217 currency code
     * @return string
     *
     */
    public static function formatAmount($value, $currencyCode) {
        $decimals = in_array(strtoupper($currencyCode), SEATREG_PAYPAL_ZERO_DECIMAL_CURRENCIES) ? 0 : 2;

        return number_format((float)$value, $decimals, '.', '');
    }

    /**
     *
     * Check if a PayPal error response contains the given issue
     * @param object $responseBody Decoded PayPal error response
     * @param string $issue Issue name to look for
     * @return boolean
     *
     */
    public static function hasIssue($responseBody, $issue) {
        if( empty($responseBody->details) || !is_array($responseBody->details) ) {
            return false;
        }

        foreach($responseBody->details as $detail) {
            if( isset($detail->issue) && $detail->issue === $issue ) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * Build a readable error message from a PayPal error response
     * @param int $status HTTP status code
     * @param object $responseBody Decoded PayPal response
     * @return string
     *
     */
    private static function describeError($status, $responseBody) {
        $parts = array('HTTP ' . $status);

        if( isset($responseBody->name) ) {
            $parts[] = $responseBody->name;
        }

        if( isset($responseBody->error) ) {
            $parts[] = $responseBody->error;
        }

        if( isset($responseBody->message) ) {
            $parts[] = $responseBody->message;
        }

        if( isset($responseBody->error_description) ) {
            $parts[] = $responseBody->error_description;
        }

        if( !empty($responseBody->details) && is_array($responseBody->details) ) {
            foreach($responseBody->details as $detail) {
                if( isset($detail->issue) ) {
                    $parts[] = $detail->issue;
                }
            }
        }

        return implode(': ', $parts);
    }
}
