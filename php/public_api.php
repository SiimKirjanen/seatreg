<?php

add_action( 'rest_api_init', function () {
    register_rest_route( 'seatreg/v1', '/echo', array(
      'methods' => 'GET',
      'callback' => array('SeatregPublicApiService', 'echo'),
      'permission_callback' => '__return_true',
    ) );
    register_rest_route( 'seatreg/v1', '/validate-token', array(
      'methods' => 'GET',
      'callback' => array('SeatregPublicApiService', 'validateToken'),
      'permission_callback' => '__return_true',
    ) );
    register_rest_route( 'seatreg/v1', '/bookings', array(
      'methods' => 'GET',
      'callback' => array('SeatregPublicApiService', 'getBookings'),
      'permission_callback' => '__return_true',
    ) );
    register_rest_route( 'seatreg/v1', '/notification-bookings', array(
      'methods' => 'GET',
      'callback' => array('SeatregPublicApiService', 'getNotificationBookings'),
      'permission_callback' => '__return_true',
    ) );
});