<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

if( is_admin() ) {
	add_action( 'admin_menu', 'seatreg_add_plugin_menu' );
}

add_action( 'after_setup_theme', 'seatreg_remove_unnecessary_tags' );
function seatreg_remove_unnecessary_tags(){
	if( seatreg_is_registration_view_page() ) {
		 // REMOVE WP EMOJI
		 remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		 remove_action( 'wp_print_styles', 'print_emoji_styles' );
	 
		 remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		 remove_action( 'admin_print_styles', 'print_emoji_styles' );
	 
	 
		 // remove all tags from header
		 remove_action( 'wp_head', 'rsd_link' );
		 remove_action( 'wp_head', 'wp_generator' );
		 remove_action( 'wp_head', 'feed_links', 2 );
		 remove_action( 'wp_head', 'index_rel_link' );
		 remove_action( 'wp_head', 'wlwmanifest_link' );
		 remove_action( 'wp_head', 'feed_links_extra', 3 );
		 remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
		 remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
		 remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 );
		 remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
		 remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
		 remove_action( 'wp_head', 'rest_output_link_wp_head' );
		 remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		 remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	 
		 // language
		 add_filter( 'multilingualpress.hreflang_type', '__return_false' );
	}
}

add_action( 'plugins_loaded', 'seatreg_update_db_check' );
function seatreg_update_db_check() {
    if ( get_site_option( 'seatreg_db_current_version' ) != SEATREG_DB_VERSION ) {
        seatreg_set_up_db();
    }
}

add_action( 'seatreg_pending_booking_expiration', 'seatreg_pending_bookings_expiration_time_job');
function seatreg_pending_bookings_expiration_time_job() {
	SeatregJobService::pendingBookingExpirationTimeJob();
}