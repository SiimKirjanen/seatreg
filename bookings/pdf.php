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

$UTC = new DateTimeZone("UTC");

try {
	$newTZ = new DateTimeZone($_GET['zone']);
}catch(Exception $e) {
	printf(
		esc_html('Can\'t generate PDF because of Unknown or bad timezone (%s)'),
		esc_html($_GET['zone'])
	);

	exit();
}

$currentDate = new DateTime(null, $UTC);
$currentDate->setTimezone( $newTZ );

$registrationInfo = seatreg_get_options($_GET['v'])[0];
$registrations = seatreg_get_data_for_booking_file($_GET['v'], $showWhat);

$projectName = $registrationInfo->registration_name;
$customFields = json_decode($registrationInfo->custom_fields, true);
$customFieldsCount = count($customFields);
$regLen = count($registrations);

function customFieldWithValuePDF($label, $custom_data) {
	$cust_len = count($custom_data);
	$foundIt = false;
	$string = $label . ': ';
	
	for($k = 0; $k < $cust_len; $k++) {
		if($custom_data[$k]['label'] == $label) {
			if($custom_data[$k]['value'] === true) {
				$string .= esc_html__('Yes', 'seatreg');
			}else if($custom_data[$k]['value'] === false) {
				$string .= esc_html__('No', 'seatreg');
			}else {
				$string .= esc_html($custom_data[$k]['value']);
			}
			$foundIt = true;

			break;
		}
	}

	if(!$foundIt) {
		$string .= esc_html__(' not set', 'seatreg');
	}

	return $string;
}

class PDF extends tFPDF {
	function Header() {
		$this->SetFont('Arial','B',14);
		$this->Image(SEATREG_PLUGIN_FOLDER_DIR. 'img/seatreg_logo.png',9,5,30);
		
		// Move to the right
		$this->Cell(70);
		// Title
		$this->Cell(30,20,$GLOBALS['projectName'],0,0,'C');

		$this->Cell(40);
		$this->SetFont('Arial','',10);
		$UTC = new DateTimeZone("UTC");

		try {
			$newTZ = new DateTimeZone($_GET['zone']);
		}catch(Exception $e) {
			printf(
				esc_html('Can\'t generate PDF because of Unknown or bad timezone (%s)'),
				esc_html($_GET['zone'])
			);

			exit(); 
		}

		$currentDate = new DateTime(null, $UTC);
		$currentDate->setTimezone( $newTZ );
		
		$this->Cell(30,0,$currentDate->format('Y-M-d H:i:s'),0,0,'C');
		// Line break
		$this->Ln(20);
	}

	// Page footer
	function Footer() {
		// Position at 1.5 cm from bottom
		$this->SetY(-15);
		// Arial italic 8
		$this->SetFont('Arial','I',8);
		// Page number
		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
	}
}

// Instanciation of inherited class
$title = esc_html($projectName . ' ' . $currentDate->format('Y-M-d'));
$pdf = new PDF();
$pdf->SetTitle( $title );
$pdf->SetAuthor('SeatReg WordPress');
$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf', true);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('DejaVu','U',12);
$pdf->Cell(20,10,'Seat number',0,0,'C');
$pdf->Cell(6);
$pdf->Cell(40,10,'Room name',0,0,'C');
$pdf->Cell(40,10,'Date',0,0,'C');
$pdf->Cell(40,10,'Email',0,0,'C');
$pdf->Cell(40,10,'Status',0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('DejaVu','',10);

$UTC = new DateTimeZone("UTC");

try {
	$newTZ = new DateTimeZone($_GET['zone']);
}catch(Exception $e) {
	printf(
		esc_html('Can\'t generate PDF because of Unknown or bad timezone (%s)'),
		esc_html($_GET['zone'])
	);

	exit();
}

for($i=0;$i<$regLen;$i++) {
	$registrantCustomData = json_decode($registrations[$i]->custom_field_data, true);
	$status = ($registrations[$i]->status === "2") ? "Approved" : "Pending";

	$pdf->Cell(20,10, esc_html($registrations[$i]->seat_nr),0,0,'C');
	$pdf->Cell(6);
	$pdf->Cell(40,10, esc_html($registrations[$i]->room_name),0,0,'C');

	$date = new DateTime($registrations[$i]->booking_date, $UTC );
	$date->setTimezone( $newTZ );

	$pdf->Cell(40,10,$date->format('Y-M-d H:i:s'),0,0,'C');
	$pdf->Cell(40,10, esc_html($registrations[$i]->email),0,0,'C');
	$pdf->Cell(40,10,$status,0,1,'C');
	$pdf->Cell(80,10,'Name: ' . esc_html($registrations[$i]->first_name) . ' ' . esc_html($registrations[$i]->last_name),0,1);

	if($status =='Approved') {
		$date = new DateTime($registrations[$i]->booking_confirm_date, $UTC );
		$date->setTimezone( $newTZ );
		$pdf->Cell(80,10,'Approve date: ' . $date->format('Y-M-d H:i:s'),0,1);
	}

	for($j = 0; $j < $customFieldsCount; $j++) {
		$pdf->Cell(40,10,customFieldWithValuePDF($customFields[$j]['label'], $registrantCustomData),0,1);
	}

	$pdf->Ln(10);
}
	
$pdf->Output($title .'.pdf', 'I');	