<?php

add_action( 'rest_api_init', function () {
    register_rest_route( 'seatreg/v1', '/echo', array(
      'methods' => 'GET',
      'callback' => array('SeatregPublicApiService', 'echo'),
      'permission_callback' => '__return_true',
    ) );
});