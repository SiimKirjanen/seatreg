<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregPublicApiService {

    /**
     *
     * Validate public API request
     * @param object $request WP_REST_Request
     * @return WP_Error|object Returns WP_Error when validation fails. Returns API token object when validation succeeds
     * 
    */
    public static function validateApiRequest(WP_REST_Request $request) {
        $apiTokenParam = $request->get_param( 'api_token' );

        if( !$apiTokenParam ) {
            return new WP_Error( 'no_token', 'Token not provided', array( 'status' => 401 ) );
        }

        $apiToken = SeatregApiTokenRepository::getApiToken($apiTokenParam);

        if( !$apiToken ) {
            return new WP_Error( 'token_not_found', 'Token not valid', array( 'status' => 401 ) );
        }

        if( !$apiToken->public_api_enabled ) {
            return new WP_Error( 'public_api_not_enabled', 'SeatReg public API not enabled', array( 'status' => 403 ) );
        }

        return $apiToken;
    }

    public static function echo( WP_REST_Request $request ) {
        return (object) ['message' => SEATREG_API_OK_MESSAGE];
    }

    public static function validateToken( WP_REST_Request $request ) {
        $apiTokenOrError = self::validateApiRequest($request);

        if( is_wp_error( $apiTokenOrError ) ) {
            return $apiTokenOrError;
        }
        return (object) [
            'message' => SEATREG_API_OK_MESSAGE,
            'apiToken' => $apiTokenOrError->api_token,
            'id' => $apiTokenOrError->id,
            'registrationName' => $apiTokenOrError->registration_name
        ];
    }

    public static function getBookings( WP_REST_Request $request ) {
        $apiTokenOrError = self::validateApiRequest($request);

        if( is_wp_error( $apiTokenOrError ) ) {
            return $apiTokenOrError;
        }

        $bookings = SeatregBookingRepository::getConfirmedAndApprovedBookingsByRegistrationCode($apiTokenOrError->registration_code);

        return (object) [
            'message' => SEATREG_API_OK_MESSAGE,
            'bookings' => $bookings
        ];
    }

    public static function insertApiToken($registrationCode, $apiToken) {
        global $seatreg_db_table_names;
	    global $wpdb;

        return $wpdb->insert(
    		$seatreg_db_table_names->table_seatreg_api_tokens,
    		array(
    			'registration_code' => $registrationCode,
                'api_token' => $apiToken
    		),
    		'%s'
    	);
    }

    public static function deleteApiToken($apiToken) {
        global $seatreg_db_table_names;
	    global $wpdb;

        return $wpdb->delete( 
			$seatreg_db_table_names->table_seatreg_api_tokens,
			array('api_token' => $apiToken), 
			'%s'
		);
    }
}