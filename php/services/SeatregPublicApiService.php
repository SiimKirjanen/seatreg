<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregPublicApiService {

    public static function echo( WP_REST_Request $request ) {
        return (object) ['message' => SEATREG_API_OK_MESSAGE];
    }

    public static function validateToken( WP_REST_Request $request ) {
        $apiTokenParam = $request->get_param( 'api_token' );

        if( !$apiTokenParam ) {
            return new WP_Error( 'no_token', 'Token not provided', array( 'status' => 401 ) );
        }
        
        $apiToken = SeatregApiTokenRepository::getApiToken($apiTokenParam);

        if( !$apiToken ) {
            return new WP_Error( 'token_not_found', 'Token not found', array( 'status' => 401 ) );
        }

        return (object) [
            'message' => SEATREG_API_OK_MESSAGE,
            'siteUrl' => get_site_url(),
            'apiToken' => $apiToken->api_token,
            'id' => $apiToken->id,
        ];
    }

    public static function getBookings( WP_REST_Request $request ) {
        $apiTokenParam = $request->get_param( 'api_token' );

        if( !$apiTokenParam ) {
            return new WP_Error( 'no_token', 'Token not provided', array( 'status' => 401 ) );
        }

        $apiToken = SeatregApiTokenRepository::getApiToken($apiTokenParam);

        if( !$apiToken ) {
            return new WP_Error( 'token_not_found', 'Token not found', array( 'status' => 401 ) );
        }

        $bookings = SeatregBookingRepository::getConfirmedAndApprovedBookingsByRegistrationCode($apiToken->registration_code);

        return (object) [
            'message' => SEATREG_API_OK_MESSAGE,
            'bookings' => $bookings
        ];
    }
}