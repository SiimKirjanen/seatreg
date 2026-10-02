<?php
/**
 * Puts the registrations, tokens and bookings the API collection asks about into the
 * wp-env site. Run through WP-CLI (npm run test:api:seed). It removes what an earlier
 * run left behind first, so the data is the same however many times it runs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb, $seatreg_db_table_names;

const SEATREG_API_TEST_ROOM_UUID = 'api-test-room';

function seatreg_api_test_layout() {
	$seat = function ( $id, $nr, $x ) {
		return array(
			'legend'      => 'noLegend',
			'xPosition'   => $x,
			'yPosition'   => 0,
			'width'       => 31,
			'height'      => 31,
			'color'       => '#61B329',
			'hoverText'   => 'nohover',
			'id'          => $id,
			'canRegister' => 'true',
			'seat'        => $nr,
			'status'      => 'noStatus',
			'zIndex'      => 0,
			'price'       => 0,
			'lock'        => false,
		);
	};

	return wp_json_encode(
		array(
			'global'   => array(
				'roomLocator' => 1,
				'boxCounter'  => 3,
			),
			'roomData' => array(
				array(
					'skeleton' => array(
						'width'     => 32,
						'height'    => 32,
						'countX'    => 10,
						'countY'    => 10,
						'marginX'   => 0,
						'marginY'   => 0,
						'buildGrid' => 1,
					),
					'room'     => array(
						'id'              => 1,
						'uuid'            => SEATREG_API_TEST_ROOM_UUID,
						'name'            => 'API hall',
						'text'            => '',
						'legends'         => array(),
						'width'           => 400,
						'height'          => 200,
						'seatCounter'     => 3,
						'backgroundImage' => null,
						'description'     => '',
						'order'           => 0,
					),
					'boxes'    => array(
						$seat( 'apiseat1', 1, 0 ),
						$seat( 'apiseat2', 2, 40 ),
						$seat( 'apiseat3', 3, 80 ),
					),
				),
			),
		)
	);
}

$seatreg_api_test_layout_validation = SeatregDataValidation::layoutDataIsCorrect( seatreg_api_test_layout() );

if ( ! $seatreg_api_test_layout_validation->valid ) {
	WP_CLI::error( 'Seed layout is invalid: ' . $seatreg_api_test_layout_validation->errorMessage );
}

$seatreg_api_test_registrations = array(
	array(
		'code'     => 'apitest001',
		'name'     => 'API test registration',
		'token'    => 'apitest-token-enabled',
		'options'  => array( 'public_api_enabled' => 1 ),
		'bookings' => array(
			array( 'Approved', 'apiseat1', 1, SEATREG_BOOKING_APPROVED, null, 0 ),
			array( 'Pending', 'apiseat2', 2, SEATREG_BOOKING_PENDING, null, 0 ),
			array( 'Unconfirmed', 'apiseat3', 3, SEATREG_BOOKING_DEFAULT, null, 0 ),
			array( 'Deleted', 'apiseat3', 3, SEATREG_BOOKING_APPROVED, null, 1 ),
		),
	),
	array(
		'code'     => 'apitest002',
		'name'     => 'API test registration without API',
		'token'    => 'apitest-token-disabled',
		'options'  => array( 'public_api_enabled' => 0 ),
		'bookings' => array(),
	),
	array(
		'code'     => 'apitest003',
		'name'     => 'API test calendar registration',
		'token'    => 'apitest-token-calendar',
		'options'  => array(
			'public_api_enabled' => 1,
			'using_calendar'     => 1,
			'calendar_dates'     => '2030-01-10,2030-01-11',
		),
		'bookings' => array(
			array( 'First day', 'apiseat1', 1, SEATREG_BOOKING_APPROVED, '2030-01-10', 0 ),
			array( 'Second day', 'apiseat1', 1, SEATREG_BOOKING_APPROVED, '2030-01-11', 0 ),
		),
	),
);

foreach ( $seatreg_api_test_registrations as $registration ) {
	foreach ( array( 'table_seatreg', 'table_seatreg_options', 'table_seatreg_bookings', 'table_seatreg_api_tokens' ) as $table ) {
		$wpdb->delete( $seatreg_db_table_names->$table, array( 'registration_code' => $registration['code'] ) );
	}

	$wpdb->insert(
		$seatreg_db_table_names->table_seatreg,
		array(
			'registration_code'             => $registration['code'],
			'registration_name'             => $registration['name'],
			'registration_create_timestamp' => time(),
			'registration_layout'           => seatreg_api_test_layout(),
		)
	);

	$wpdb->insert(
		$seatreg_db_table_names->table_seatreg_options,
		array_merge( array( 'registration_code' => $registration['code'] ), $registration['options'] )
	);

	$wpdb->insert(
		$seatreg_db_table_names->table_seatreg_api_tokens,
		array(
			'registration_code' => $registration['code'],
			'api_token'         => $registration['token'],
		)
	);

	foreach ( $registration['bookings'] as $index => list( $firstName, $seatId, $seatNr, $status, $calendarDate, $isDeleted ) ) {
		$wpdb->insert(
			$seatreg_db_table_names->table_seatreg_bookings,
			array(
				'registration_code' => $registration['code'],
				'first_name'        => $firstName,
				'last_name'         => 'Booker',
				'email'             => 'api-test@example.com',
				'seat_id'           => $seatId,
				'seat_nr'           => $seatNr,
				'room_uuid'         => SEATREG_API_TEST_ROOM_UUID,
				'booking_date'      => time(),
				'status'            => $status,
				'booking_id'        => $registration['code'] . '-booking-' . $index,
				'conf_code'         => $registration['code'] . '-conf-' . $index,
				'booker_email'      => 'api-test@example.com',
				'calendar_date'     => $calendarDate,
				'is_deleted'        => $isDeleted,
			)
		);
	}
}

WP_CLI::success( 'Seeded ' . count( $seatreg_api_test_registrations ) . ' API test registrations.' );
