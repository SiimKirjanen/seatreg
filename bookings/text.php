<?php

require_once('../php/load_wp.php');
require_once('../php/seatreg_functions.php');
require_once('../php/libs/tfpdf/tfpdf.php');

seatreg_bookings_is_user_logged_in(); 

$showWhat = 'all';

if(!isset($_GET['s2']) && isset($_GET['s1'])) {
	$showWhat = 'pending';
}

if(!isset($_GET['s1']) && isset($_GET['s2'])) {
	$showWhat = 'confirmed';
}

if(empty($_GET['zone'])) {
	echo 'Timezone is missing';
	exit();
}

$registrationInfo = seatreg_get_options($_GET['v'])[0];

$registrations = seatreg_get_data_for_booking_file($_GET['v'], $showWhat);
$projectName = $registrationInfo->registration_name;
$customFields = json_decode($registrationInfo->custom_fields, true);
$customFieldsCount = count($customFields);
$regLen = count($registrations);
$UTC = new DateTimeZone("UTC");

try {
	$newTZ = new DateTimeZone($_GET['zone']);
}catch(Exception $e) {
	echo 'Cant generate PDF because of Unknown or bad timezone (', $_GET['zone'], ')';
	exit();
}

$currentDate = new DateTime(null, $UTC);
$currentDate->setTimezone( $newTZ );

header("Content-type: text/plain");
header('Content-Disposition: attachment; filename="'.$projectName.' '.$currentDate->format('Y-M-d').'.txt"');

function customFieldWithValueText($label, $custom_data) {
	$cust_len = count($custom_data);
	//echo 'Custom field length: ',$cust_len, '<br>';
	$foundIt = false;
	$string = $label . ': ';
	//echo '-----Alustan otsinguga----<br>';

	for($k = 0; $k < $cust_len; $k++) {
		//echo 'Otsin: ', $label, '<br>';
		//echo 'Leidsin: ', $custom_data[$k]['label'], '<br>';
		if($custom_data[$k]['label'] == $label) {
			//echo 'Match leitud!!!!!!!!!!!!!!';
			$string .= $custom_data[$k]['value'];
			$foundIt = true;
			break;
		}else {
			//echo 'Label: ',$label, 'ei võrdu: ',$custom_data[$k]['label'], '<br />';
			//echo 'Ei olnud match<br></br>';
		}
	}

	if(!$foundIt) {
		$string .= ' not set';
	}

	return $string;
}

echo $projectName, " bookings\r\n", 'Date: ', $currentDate->format('Y-M-d H:i:s'), "\r\n\r\n";

echo 'NR, Room, Name, Registration date, Status', "\r\n\r\n";

for($i=0;$i<$regLen;$i++) {
	$registrantCustomData = json_decode($registrations[$i]->custom_field_data, true);
	$status = ($registrations[$i]->status == 2) ? "Confirmed" : "Pending";
	$date = new DateTime($registrations[$i]->registration_date, $UTC );
	$date->setTimezone( $newTZ );

	echo $registrations[$i]->seat_nr, ', ',$registrations[$i]->room_name, ', ',$registrations[$i]->first_name, ' ',$registrations[$i]->last_name,', ', $date->format('Y-M-d H:i:s'), ', ', $status, "\r\n";

	for($j = 0; $j < $customFieldsCount; $j++) {
		echo customFieldWithValueText($customFields[$j]->label, $registrantCustomData), "\r\n\r\n";
	}

	if($status == "Confirmed") {
		$date = new DateTime($registrations[$i]->registration_confirm_date, $UTC );
		$date->setTimezone( $newTZ );
		echo 'Confirmation date: ', $date->format('Y-M-d H:i:s'), "\r\n";
	}
	echo "\r\n";
}