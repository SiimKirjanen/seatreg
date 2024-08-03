<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregRandomGenerator {
	public static function generateApiToken() {
		return rand(1000000000,9999999999);
	}
	public static function generateRandom($salt) {
		return sha1(mt_rand(10000,99999).time().$salt);
	}
}