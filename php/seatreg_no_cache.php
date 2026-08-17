<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//every plugin owned front end URL is routed by the seatreg query parameter, see seatreg_filters.php
function seatreg_is_dynamic_public_page() {
	return isset( $_GET['seatreg'] );
}

//set while this file is included, because some caches decide whether to store the response before any hook we could use
if ( seatreg_is_dynamic_public_page() ) {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	if ( ! defined( 'DONOTCACHEDB' ) ) {
		define( 'DONOTCACHEDB', true );
	}

	/*
	 * The registration data is printed as an inline script, see enqueue_public.php. Script optimisation
	 * bundles it into a static file that keeps being served after the data has changed, so the seat map
	 * shows outdated colours, availability and bookings.
	 */
	if ( ! defined( 'DONOTMINIFY' ) ) {
		define( 'DONOTMINIFY', true );
	}
	if ( ! defined( 'DONOTROCKETOPTIMIZE' ) ) {
		define( 'DONOTROCKETOPTIMIZE', true );
	}

	add_filter( 'wpo_minify_run_on_page', '__return_false' );
	add_filter( 'autoptimize_filter_noptimize', '__return_true' );
}

//LiteSpeed caches query string URLs by default, so it is the one most likely to store these pages
add_action( 'wp', 'seatreg_disable_litespeed_cache' );
function seatreg_disable_litespeed_cache() {
	if ( ! seatreg_is_dynamic_public_page() ) {
		return;
	}

	do_action( 'litespeed_control_set_nocache', 'SeatReg dynamic page' );
}
