<?php

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_functions.php' );
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/tfpdf/tfpdf.php' );

seatreg_bookings_is_user_logged_in(); 

$showWhat = 'all';

if(!isset($_GET['s2']) && isset($_GET['s1'])) {
	$showWhat = 'pending';
}

if(!isset($_GET['s1']) && isset($_GET['s2'])) {
	$showWhat = 'confirmed';
}

if(empty($_GET['zone'])) {
	esc_html_e('Timezone is missing', 'seatreg');

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
	printf(
		esc_html('Can\'t generate text because of Unknown or bad timezone (%s)'),
		esc_html($_GET['zone'])
	);

	exit();
}

$currentDate = new DateTime(null, $UTC);
$currentDate->setTimezone( $newTZ );

header("Content-type: text/plain");
header('Content-Disposition: attachment; filename="'. esc_html($projectName) .' '.$currentDate->format('Y-M-d').'.txt"');

function customFieldWithValueText($label, $custom_data) {
	$cust_len = count($custom_data);
	$foundIt = false;
	$string = $label . ': ';

	for($k = 0; $k < $cust_len; $k++) {
		if($custom_data[$k]['label'] == $label) {
			$string .= esc_html($custom_data[$k]['value']);
			$foundIt = true;
			break;
		}
	}

	if(!$foundIt) {
		$string .= esc_html__(' not set', 'seatreg');
	}

	return $string;
}

echo esc_html($projectName), " bookings\r\n", 'Date: ', $currentDate->format('Y-M-d H:i:s'), "\r\n\r\n";

echo 'NR, Room, Name, Registration date, Status', "\r\n\r\n";

for($i=0;$i<$regLen;$i++) {
	$registrantCustomData = json_decode($registrations[$i]->custom_field_data, true);
	$status = ($registrations[$i]->status === "2") ? "Approved" : "Pending";
	$date = new DateTime($registrations[$i]->booking_date, $UTC );
	$date->setTimezone( $newTZ );

	echo esc_html($registrations[$i]->seat_nr), ', ', esc_html($registrations[$i]->room_name), ', ', esc_html($registrations[$i]->first_name), ' ', esc_html($registrations[$i]->last_name),', ', $date->format('Y-M-d H:i:s'), ', ', $status, "\r\n";

	for($j = 0; $j < $customFieldsCount; $j++) {
		echo customFieldWithValueText($customFields[$j]['label'], $registrantCustomData), "\r\n\r\n";
	}

	if($status == "Approved") {
		$date = new DateTime($registrations[$i]->booking_confirm_date, $UTC );
		$date->setTimezone( $newTZ );
		echo 'Confirmation date: ', $date->format('Y-M-d H:i:s'), "\r\n";
	}
	echo "\r\n";
}