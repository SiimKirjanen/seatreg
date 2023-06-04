<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregRandomGenerator {
	public static function generateApiToken() {
		return rand(1000000000,9999999999);
	}
}