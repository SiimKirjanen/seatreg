<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/*
================================
registration functions
================================
*/

global $wpdb;
global $seatreg_db_table_names;

$seatreg_db_table_names = new stdClass();
$seatreg_db_table_names->table_seatreg = $wpdb->prefix . "seatreg";
$seatreg_db_table_names->table_seatreg_options = $wpdb->prefix . "seatreg_options";
$seatreg_db_table_names->table_seatreg_bookings = $wpdb->prefix . "seatreg_bookings";

function seatreg_get_options_reg($code) {
	global $wpdb;
	global $seatreg_db_table_names;

	return $wpdb->get_row( $wpdb->prepare(
		"SELECT a.*, b.* 
		FROM $seatreg_db_table_names->table_seatreg AS a
		INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
		ON a.registration_code = b.registration_code
		WHERE a.registration_code = %s",
		$code
	) );
}