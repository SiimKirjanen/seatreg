<?php
/**
 * Plugin Name: SeatReg E2E fixtures
 * Description: Makes the site state a few specs need and the admin screens make
 *              awkward to click together - a post carrying a shortcode, a user who
 *              is not an administrator. Mapped into the wp-env site by .wp-env.json
 *              and never shipped with the plugin.
 *
 * Unlike the mail log this needs no switch: nothing here happens until it is asked
 * for, and only an administrator may ask. Whatever it makes is left behind, exactly
 * as the registrations the suite creates are (tests/e2e/utils/registrations.js), so
 * every caller names what it asks for uniquely.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function seatreg_e2e_fixtures_guard() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}
}

/* Published straight away and by the administrator asking for it, so the shortcode
   is rendered for a visitor the moment the address is handed back. */
add_action(
	'wp_ajax_seatreg_e2e_create_post',
	function () {
		seatreg_e2e_fixtures_guard();

		$title   = isset( $_GET['title'] ) ? sanitize_text_field( wp_unslash( $_GET['title'] ) ) : '';
		$content = isset( $_GET['content'] ) ? wp_unslash( $_GET['content'] ) : '';

		if ( '' === $title ) {
			wp_send_json_error( 'title is required', 400 );
		}

		$id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'post',
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			wp_send_json_error( $id->get_error_message(), 500 );
		}

		wp_send_json(
			array(
				'id'  => $id,
				'url' => get_permalink( $id ),
			)
		);
	}
);

/* For asking what someone without the plugin's capabilities can reach. The role is
   whatever the caller names, so nothing here decides what that proves. */
add_action(
	'wp_ajax_seatreg_e2e_create_user',
	function () {
		seatreg_e2e_fixtures_guard();

		$username = isset( $_GET['username'] ) ? sanitize_user( wp_unslash( $_GET['username'] ) ) : '';
		$password = isset( $_GET['password'] ) ? wp_unslash( $_GET['password'] ) : '';
		$role     = isset( $_GET['role'] ) ? sanitize_key( wp_unslash( $_GET['role'] ) ) : 'subscriber';

		if ( '' === $username || '' === $password ) {
			wp_send_json_error( 'username and password are required', 400 );
		}

		$id = wp_insert_user(
			array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $username . '@example.com',
				'role'       => $role,
			)
		);

		if ( is_wp_error( $id ) ) {
			wp_send_json_error( $id->get_error_message(), 500 );
		}

		wp_send_json(
			array(
				'id'       => $id,
				'username' => $username,
			)
		);
	}
);
