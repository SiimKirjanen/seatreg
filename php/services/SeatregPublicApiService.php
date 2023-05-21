<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregPublicApiService {

    public static function echo( WP_REST_Request $request ) {
        return (object) ['message' => 'echo'];
    }

    public static function validateToken( WP_REST_Request $request ) {
        $apiToken = $request->get_param( 'api_token' );
    }
}