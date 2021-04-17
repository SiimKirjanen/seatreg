<?php
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_functions.php' );
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/xlsxwriter.class.php' );

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

function customFieldWithValueXlsx($label, $custom_data) {
	$cust_len = count(is_array($custom_data) ? $custom_data : []);
	$foundIt = false;
	$string = '';

	for($k = 0; $k < $cust_len; $k++) {
		if($custom_data[$k]['label'] == $label) {
			if($custom_data[$k]['value'] === true) {
				$string = esc_html__('Yes', 'seatreg');
			}else if($custom_data[$k]['value'] === false) {
				$string = esc_html__('No', 'seatreg');
			}else {
				$string = $custom_data[$k]['value'];
			}

			$foundIt = true;
			break;
		}
	}

	if(!$foundIt) {
		$string = esc_html__(' not set', 'seatreg');
	}

	return $string;
}

$header = array(
	'Seat number'=>'string',
	'Room name'=>'string',
	'Name' =>'string',
	'Email'=>'string',
	'Date'=>'string',
	'Status'=>'string',
	'Approve date' => 'string'
);

$data = array();
$UTC = new DateTimeZone("UTC");

try {
	$newTZ = new DateTimeZone($_GET['zone']);
}catch(Exception $e) {
	printf(
		esc_html('Can\'t generate XLSX because of Unknown or bad timezone (%s)'),
		esc_html($_GET['zone'])
	);

	exit();
}

$currentDate = new DateTime(null, $UTC);
$currentDate->setTimezone( $newTZ );

for($i=0;$i<$regLen;$i++) {	
	$registrantCustomData = json_decode($registrations[$i]->custom_field_data, true);
	$status = ($registrations[$i]->status === "2") ? "Approved" : "Pending";
	$date = new DateTime($registrations[$i]->booking_date, $UTC );
	$date->setTimezone( $newTZ );
	$registretionData = array(esc_html($registrations[$i]->seat_nr), esc_html($registrations[$i]->room_name), esc_html($registrations[$i]->first_name) . ' ' . esc_html($registrations[$i]->last_name),  esc_html($registrations[$i]->email), $date->format('Y-M-d H:i:s'), $status);

	if($status =='Approved') {
		$date = new DateTime($registrations[$i]->booking_confirm_date, $UTC );
		$date->setTimezone( $newTZ );
		$registretionData[] = $date->format('Y-M-d H:i:s');
	}else {
		$registretionData[] = '';
	}

	for($j = 0; $j < $customFieldsCount; $j++) {
		$header[$customFields[$j]['label']] = 'string';
		$registretionData[] = customFieldWithValueXlsx($customFields[$j]['label'], $registrantCustomData);
	}
	$data[] = $registretionData;
}

$filename =  esc_html($projectName) . ' ' . $currentDate->format('Y-M-d') . ".xlsx";
header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');
	
$writer = new XLSXWriter();
$writer->setAuthor('SeatReg WordPress');
$writer->writeSheet($data,'Sheet1',$header);
$writer->writeToStdOut();

exit(0);