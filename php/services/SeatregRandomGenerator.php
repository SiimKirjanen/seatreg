<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregRandomGenerator {
	public static function generateApiToken() {
		return rand(1000000000,9999999999);
	}
	public static function generateRandom() {
		return bin2hex(random_bytes(20));
	}
}