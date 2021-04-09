<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/*
================================
registration functions
================================
*/

//require_once('../php/JsonResponse.php');


global $wpdb;
global $seatreg_db_table_names;

$seatreg_db_table_names = new stdClass();
$seatreg_db_table_names->table_seatreg = $wpdb->prefix . "seatreg";
$seatreg_db_table_names->table_seatreg_options = $wpdb->prefix . "seatreg_options";
$seatreg_db_table_names->table_seatreg_bookings = $wpdb->prefix . "seatreg_bookings";

function seatreg_stats_for_registration_reg($structure, $code) {
	global $wpdb;
	global $seatreg_db_table_names;

	$pendingBookings = $wpdb->get_results( $wpdb->prepare(
		"SELECT room_uuid,
		COUNT(id) AS total
		FROM  $seatreg_db_table_names->table_seatreg_bookings
		WHERE registration_code = %s
		AND status = 1
		GROUP BY room_uuid",
		$code
	) );

	$confirmedBookings = $wpdb->get_results( $wpdb->prepare(
		"SELECT room_uuid,
		COUNT(id) AS total
		FROM  $seatreg_db_table_names->table_seatreg_bookings
		WHERE registration_code = %s
		AND status = 2
		GROUP BY room_uuid",
		$code
	) );

	$statsArray = seatreg_get_seats_stats($structure, $pendingBookings, $confirmedBookings);

	return $statsArray;	
}

//get info of seats. how many, open, bron... in each room and total info
function seatreg_get_seats_stats($struct, $bronRegistrations, $takenRegistrations) {
	$registration = json_decode($struct);

	if(!isset($registration->roomData)) {
		return [];
	}

	$bronLength = count($bronRegistrations);
	$takenLength = count($takenRegistrations);
	$regStructure = $registration->roomData;
	$roomCount = count(is_array($regStructure) ? $regStructure : []);
	$howManyRegSeats = 0;
	$howManyOpenSeats = 0;
	$howManyBronSeats= 0;
	$howManyTakenSeats = 0;
	$howManyCustomBoxes = 0;
	$statsArray = array();
	$roomsInfo = array();

	for($i = 0; $i < $roomCount; $i++) {
		$roomBoxes = $regStructure[$i]->boxes;
		//find how many bron seats in this room
		$roomBoxCount = count($roomBoxes);
		$roomRegSeats = 0;  //how many reg seats
		$roomOpenSeats = 0; //how many open reg seats
		$roomTakenSeats = 0; //how many taken seats
		$roomBronSeats = 0;	//bron seats
		$roomCustomBoxes = 0;

		for($k = 0; $k < $bronLength; $k++) {  
			if( $regStructure[$i]->room->uuid == $bronRegistrations[$k]->room_uuid ) { //find how many bron seats in this room
				$roomBronSeats = $bronRegistrations[$k]->total;
				$howManyBronSeats += $bronRegistrations[$k]->total;

				break;
			}
		}

		for($k = 0; $k < $takenLength; $k++) {
			if($regStructure[$i]->room->uuid == $takenRegistrations[$k]->room_uuid) { //find how many taken seats in this room
				$roomTakenSeats = $takenRegistrations[$k]->total;
				$howManyTakenSeats += $takenRegistrations[$k]->total;

				break;
			}
		}
		
		for($j = 0; $j < $roomBoxCount; $j++) {
			if($roomBoxes[$j]->canRegister === 'true') {
				if($roomBoxes[$j]->status === 'noStatus') {
					$howManyOpenSeats++;
					$roomOpenSeats++;
				}

				$howManyRegSeats++;
				$roomRegSeats++;
			}else {
				$howManyCustomBoxes++;
				$roomCustomBoxes++;
			}
		}

		$roomsInfo[] = array(
			'roomUuid' => $regStructure[$i]->room->uuid,
			'roomSeatsTotal' => $roomRegSeats,
			'roomOpenSeats' => $roomRegSeats - $roomTakenSeats - $roomBronSeats,
			'roomTakenSeats' => $roomTakenSeats,
			'roomBronSeats' => $roomBronSeats,
			'roomCustomBoxes' => $roomCustomBoxes
		);
	}

	$statsArray['seatsTotal'] = $howManyRegSeats;
	$statsArray['openSeats'] = $howManyOpenSeats - $howManyBronSeats - $howManyTakenSeats;
	$statsArray['bronSeats'] = $howManyBronSeats;
	$statsArray['takenSeats'] = $howManyTakenSeats;
	$statsArray['roomCount'] = $roomCount;
	$statsArray['roomsInfo'] = $roomsInfo;

	return $statsArray;
}

function seatreg_get_registration_bookings_reg($code, $show_bookings) {
	global $wpdb;
	global $seatreg_db_table_names;

	if($show_bookings == 1) {
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT seat_id, room_uuid, status, CONCAT(first_name, ' ', last_name) AS reg_name
			FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE registration_code = %s
			AND (status = '1' OR status = '2')",
			$code
		) );
	}else {
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT seat_id, room_uuid, status 
			FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE registration_code = %s
			AND (status = '1' OR status = '2')",
			$code
		) );
	}

	return $bookings;
}

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