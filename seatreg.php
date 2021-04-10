<?php
/*
	Plugin Name: SeatReg
	Plugin URI: https://github.com/SiimKirjanen/seatreg_wordpress
	Description: Create and manage seat registrations. Design your own seat maps and manage seat bookings
	Author: Siim Kirjanen
	Text Domain: seatreg
	Version: 1.0.0
	Requires at least: 5.4.4
	Requires PHP: 7.2.28
	License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

if( is_admin() ) {
	require( plugin_dir_path( __FILE__ ) . 'php/enqueue_admin.php' );
	require( plugin_dir_path( __FILE__ ) . 'php/seatreg_admin_panel.php' );	
}

require_once( plugin_dir_path( __FILE__ ) . 'php/seatreg_functions.php' );
require_once( plugin_dir_path( __FILE__ ) . 'php/SeatregJsonResponse.php' );

function seatreg_plugin_activate() {
	seatreg_set_up_db();
}

//actions
if( is_admin() ) {
	add_action('admin_menu', 'seatreg_add_plugin_menu');
}

//remove queued styles from registration view page
add_action('wp_print_styles', 'seatreg_remove_all_styles', 100);
function seatreg_remove_all_styles() {
	if( seatreg_is_registration_view_page() ) {
		global $wp_styles;
		$allowedToLoad = array('seatreg-registration-style', 'google-open-sans');
    	$wp_styles->queue = $allowedToLoad;
	}
}

//only allow spesific scripts to load on registration view page
add_action('wp_print_scripts', 'seatreg_remove_all_scripts', 100);
function seatreg_remove_all_scripts() {
	if( seatreg_is_registration_view_page() ) {
		global $wp_scripts;
		$allowedToLoad = array('jquery', 'seatreg-registration', 'date-format', 'jquery-powertip', 'iscroll-zoom', 'modernizr');
		$wp_scripts->queue = $allowedToLoad;
	}
}

add_action( 'wp_enqueue_scripts', 'seatreg_public_scripts_and_styles' );
function seatreg_public_scripts_and_styles() {
	if ( seatreg_is_registration_view_page() ) {
		$manifestFileContents = file_get_contents(SEATREG_PLUGIN_FOLDER_URL . 'rev-manifest.json');
		$manifest = json_decode($manifestFileContents, true);
		wp_enqueue_style('google-open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:400,700', array(), '1.0.0', 'all');
		wp_enqueue_style('seatreg-registration-style', SEATREG_PLUGIN_FOLDER_URL . 'registration/css/' . $manifest['registration.min.css'] , array(), '1.0.0', 'all');
		wp_enqueue_script("jquery");
		wp_enqueue_script('modernizr', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/modernizr.custom.89593.min.js' , array(), '2.8.3', false);
		wp_enqueue_script('date-format', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/date.format.js' , array(), '1.0.0', true);
		wp_enqueue_script('iscroll-zoom', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/iscroll-zoom.js' , array(), '5.1.3', true);
		wp_enqueue_script('jquery-powertip', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/jquery.powertip.js' , array(), '1.2.0', true);
		wp_enqueue_script('seatreg-registration', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/registration.js' , array('jquery', 'date-format', 'iscroll-zoom', 'jquery-powertip'), '1.0.0', true);
	}
}

add_action( 'after_setup_theme', 'seatreg_remove_unnecessary_tags' );
function seatreg_remove_unnecessary_tags(){
	if( seatreg_is_registration_view_page() ) {
		 // REMOVE WP EMOJI
		 remove_action('wp_head', 'print_emoji_detection_script', 7);
		 remove_action('wp_print_styles', 'print_emoji_styles');
	 
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
		 add_filter('multilingualpress.hreflang_type', '__return_false');
	}
}

add_filter( 'show_admin_bar', 'seatreg_hide_admin_bar_from_front_end' );
function seatreg_hide_admin_bar_from_front_end(){
	if( seatreg_is_registration_view_page() ) {
	  return false;
	}
	return true;
}

//hooks
register_activation_hook(__FILE__, "seatreg_plugin_activate");

//filters
add_filter( 'page_template', 'seatreg_page_template' );
function seatreg_page_template( $page_template ){
    if ( seatreg_is_registration_view_page() ) {
        $page_template = plugin_dir_path( __FILE__ ) . '/registration/index.php';
    }

    return $page_template;
}

add_filter('init', 'seatreg_virtual_pages');
function seatreg_virtual_pages() {

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'pdf') {
		include SEATREG_PLUGIN_FOLDER_DIR . 'bookings/pdf.php';

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'xlsx') {
		include SEATREG_PLUGIN_FOLDER_DIR . 'bookings/xlsx.php';

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'text') {
		include SEATREG_PLUGIN_FOLDER_DIR  . 'bookings/text.php';

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'booking-confirm') {
		include SEATREG_PLUGIN_FOLDER_DIR  . 'php/booking_confirm.php';

		die();
	}

	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'booking-status') {
		include SEATREG_PLUGIN_FOLDER_DIR  . 'php/booking_check.php';

		die();
	}
}

add_filter( 'admin_body_class', 'seatreg_admin_body_class' );
function seatreg_admin_body_class($classes) {
	if( !isset($_GET['page']) ) {
		return $classes;
	}

	if( $_GET['page'] == 'seatreg-welcome' || $_GET['page'] == 'seatreg-builder' ) {
		return "$classes seatreg-map-builder-page"; 
	}

	return $classes;
}

add_filter('admin_footer_text', 'seatreg_remove_admin_footer_text');
function seatreg_remove_admin_footer_text() {
    echo '';
}