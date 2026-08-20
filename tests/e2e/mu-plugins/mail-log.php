<?php
/**
 * Plugin Name: SeatReg E2E mail log
 * Description: Captures wp_mail() instead of sending it, so the e2e suite can read
 *              the links the plugin emails. Mapped into the wp-env site by
 *              .wp-env.json and never shipped with the plugin.
 *
 * Capturing hooks pre_wp_mail, which stops wp_mail() before PHPMailer is ever built,
 * so while it is on an SMTP plugin sees nothing. It is therefore off unless a run
 * switches it on (tests/e2e/auth.setup.js, tests/e2e/mail.teardown.js). Setting
 * SEATREG_E2E_MAIL_LOG in .wp-env.json holds it on for reading mail by hand.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SEATREG_E2E_MAIL_FILE = 'seatreg-e2e-mail.log';

// An option rather than a constant, so switching it costs no wp-env restart
const SEATREG_E2E_MAIL_OPTION = 'seatreg_e2e_mail_log';

// Past this the log is started over: nothing reads an old run's mail
const SEATREG_E2E_MAIL_MAX_BYTES = 2097152;

function seatreg_e2e_mail_log_enabled() {
	if ( defined( 'SEATREG_E2E_MAIL_LOG' ) && SEATREG_E2E_MAIL_LOG ) {
		return true;
	}

	return '1' === get_option( SEATREG_E2E_MAIL_OPTION );
}

function seatreg_e2e_mail_log_path() {
	$uploads = wp_upload_dir();

	return trailingslashit( $uploads['basedir'] ) . SEATREG_E2E_MAIL_FILE;
}

/* Returning true matters as much as the logging: the site has no mail transport,
   and the plugin fails a booking whose mail it could not send. */
add_filter(
	'pre_wp_mail',
	function ( $short_circuit, $atts ) {
		if ( ! seatreg_e2e_mail_log_enabled() ) {
			return $short_circuit;
		}

		$path = seatreg_e2e_mail_log_path();

		if ( file_exists( $path ) && filesize( $path ) > SEATREG_E2E_MAIL_MAX_BYTES ) {
			unlink( $path );
		}

		// Appended under a lock: a read-modify-write would lose parallel workers' mail
		file_put_contents(
			$path,
			wp_json_encode(
				array(
					'to'      => (array) $atts['to'],
					'subject' => $atts['subject'],
					'message' => $atts['message'],
					'time'    => microtime( true ),
				)
			) . "\n",
			FILE_APPEND | LOCK_EX
		);

		return true;
	},
	10,
	2
);

/* For the run that was killed before it could switch back: an SMTP plugin quietly
   delivering nothing is a hard symptom to read backwards. */
add_action(
	'admin_notices',
	function () {
		if ( ! seatreg_e2e_mail_log_enabled() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>SeatReg E2E:</strong> outgoing mail is being captured, not sent. ';
		echo 'Run <code>wp option delete ', esc_html( SEATREG_E2E_MAIL_OPTION ), '</code> to send mail for real.</p></div>';
	}
);

// How the e2e run brackets itself
add_action(
	'wp_ajax_seatreg_e2e_mail_capture',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}

		$enable = isset( $_GET['enable'] ) && '1' === $_GET['enable'];

		update_option( SEATREG_E2E_MAIL_OPTION, $enable ? '1' : '0' );

		wp_send_json( array( 'enabled' => seatreg_e2e_mail_log_enabled() ) );
	}
);

/* The captured mail, newest last, filtered by recipient. Answers even when
   capturing is off, so the suite can say why no mail arrived. */
add_action(
	'wp_ajax_seatreg_e2e_mail_log',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}

		if ( ! seatreg_e2e_mail_log_enabled() ) {
			wp_send_json( array( 'disabled' => true ) );
		}

		$path = seatreg_e2e_mail_log_path();

		if ( ! file_exists( $path ) ) {
			wp_send_json( array() );
		}

		$to   = isset( $_GET['to'] ) ? sanitize_email( wp_unslash( $_GET['to'] ) ) : '';
		$mail = array();

		foreach ( file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
			$entry = json_decode( $line, true );

			if ( ! $entry ) {
				continue;
			}

			if ( '' === $to || in_array( $to, $entry['to'], true ) ) {
				$mail[] = $entry;
			}
		}

		wp_send_json( $mail );
	}
);
