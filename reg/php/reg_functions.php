<?php

/*
================================
registration functions
================================
*/

require_once('../php/util/load_wp.php');
require_once('../php/JsonResponse.php');

global $wpdb;
global $seatreg_db_table_names;

$seatreg_db_table_names = new stdClass();
$seatreg_db_table_names->table_seatreg = $wpdb->prefix . "seatreg";
$seatreg_db_table_names->table_seatreg_options = $wpdb->prefix . "seatreg_options";
$seatreg_db_table_names->table_seatreg_bookings = $wpdb->prefix . "seatreg_bookings";

function seatreg_stats_for_registration_reg($structure, $code) {
	global $wpdb;
	global $seatreg_db_table_names;

	$bookings = $wpdb->get_results( $wpdb->prepare(
		"SELECT room_name,
		COUNT(id) AS total
		FROM  $seatreg_db_table_names->table_seatreg_bookings
		WHERE seatreg_code = %s
		AND status = 1
		GROUP BY room_name",
		$code
	) );

	$bookings2 = $wpdb->get_results( $wpdb->prepare(
		"SELECT room_name,
		COUNT(id) AS total
		FROM  $seatreg_db_table_names->table_seatreg_bookings
		WHERE seatreg_code = %s
		AND status = 2
		GROUP BY room_name",
		$code
	) );

	$statsArray =  getSeatsStats($structure, $bookings, $bookings2);

	return $statsArray;	
}

//get info of seats. how many, open, bron... in each room and total info
function getSeatsStats($struct, $bronRegistrations, $takenRegistrations) {
	$bronLength = count($bronRegistrations);
	$takenLength = count($takenRegistrations);
	$regStructure = json_decode($struct);
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
			if( $regStructure[$i]->room[1] == $bronRegistrations[$k]->room_name ) { //find how many bron seats in this room
				$roomBronSeats = $bronRegistrations[$k]->total;
				$howManyBronSeats += $bronRegistrations[$k]->total;

				break;
			}
		}

		for($k = 0; $k < $takenLength; $k++) {
			if($regStructure[$i]->room[1] == $takenRegistrations[$k]->room_name) { //find how many taken seats in this room
				$roomTakenSeats = $takenRegistrations[$k]->total;
				$howManyTakenSeats += $takenRegistrations[$k]->total;

				break;
			}
		}
		
		for($j = 0; $j < $roomBoxCount; $j++) {
			if($roomBoxes[$j][8] == 'true') {
				if($roomBoxes[$j][10] == 'noStatus') {
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
			'roomName' => $regStructure[$i]->room[1],
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
			"SELECT seat_id, room_name, status, CONCAT(first_name, ' ', last_name) AS reg_name
			FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE seatreg_code = %s
			AND (status = '1' OR status = '2')",
			$code
		) );
	}else {
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT seat_id, room_name, status 
			FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE seatreg_code = %s
			AND (status = '1' OR status = '2')",
			$code
		) );
	}

	return $bookings;
}

function seatreg_get_options_reg($code) {
	global $wpdb;
	global $seatreg_db_table_names;

	if($code != null) {
		$options = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, b.* 
			FROM $seatreg_db_table_names->table_seatreg AS a
			INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
			ON a.registration_code = b.seatreg_code
			WHERE a.registration_code = %s",
			$code
		) );
	}else {
		$options = $wpdb->get_results( 
			"SELECT a.*, b.* 
			FROM $seatreg_db_table_names->table_seatreg AS a
			INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
			ON a.registration_code = b.seatreg_code
			ORDER BY a.registration_create_timestamp
			LIMIT 1"
		);
	}

	return $options;
}