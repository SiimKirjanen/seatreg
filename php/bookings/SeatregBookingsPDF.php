<?php
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/bookings/SeatregBookingsFile.php');
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/tfpdf/tfpdf.php' );

class SeatregPDF extends tFPDF {
    private $title = null;
    private $currentDate = null;
    private $fileName = null;

    public function __construct($title, $currentDate) {
        parent::__construct();
        $this->title = $title;
        $this->currentDate = $currentDate;
    }
    
    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Image(SEATREG_PLUGIN_FOLDER_DIR. 'img/seatreg_logo.png',9,5,30);
        
        // Move to the right
        $this->Cell(70);
        // Title
        $this->Cell(30,20,$this->title,0,0,'C');

        $this->Cell(40);
        $this->SetFont('Arial','',10);
            
        $this->Cell(30,0,$this->currentDate->format('Y-M-d H:i:s'),0,0,'C');
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

class SeatregBookingsPDF extends SeatregBookingsFile {
    private $pdf = null;

    public function __construct($showPending, $showConfirmed, $timeZone, $registrationCode) {
        parent::__construct($showPending, $showConfirmed, $timeZone, $registrationCode);

        $this->setupPDF();
	}
    private function setupPDF() {
        $this->fileName = esc_html($this->_projectName . ' ' . $this->_currentDateTime->format('Y-M-d'));

        $this->pdf = new SeatregPDF($this->_projectName, $this->_currentDateTime);
        $this->pdf->SetTitle( $this->fileName );
        $this->pdf->SetAuthor('SeatReg WordPress');
        $this->pdf->AddFont('DejaVu','','DejaVuSans.ttf', true);
        $this->pdf->AliasNbPages();
        $this->pdf->AddPage();
        $this->pdf->SetFont('DejaVu','U',12);
        $this->pdf->Cell(20,10,'Seat number',0,0,'C');
        $this->pdf->Cell(6);
        $this->pdf->Cell(40,10,'Room name',0,0,'C');
        $this->pdf->Cell(40,10,'Date',0,0,'C');
        $this->pdf->Cell(40,10,'Email',0,0,'C');
        $this->pdf->Cell(40,10,'Status',0,1,'C');
        $this->pdf->Ln(5);
        $this->pdf->SetFont('DejaVu','',10);

    }
    public function printPDF() {
        $registrationsLenght = count($this->_registrations);
        $customFieldsLength = count($this->_customFields);

        for($i=0; $i<$registrationsLenght; $i++) {
            $registrantCustomData = json_decode($this->_registrations[$i]->custom_field_data, true);
            $status = ($this->_registrations[$i]->status === "2") ? "Approved" : "Pending";
        
            $this->pdf->Cell(20, 10, esc_html($this->_registrations[$i]->seat_nr), 0, 0, 'C');
            $this->pdf->Cell(6);
            $this->pdf->Cell(40, 10, esc_html($this->_registrations[$i]->room_name), 0, 0, 'C');
        
            $date = new DateTime($this->_registrations[$i]->booking_date, $this->_UTCDateTime);
            $date->setTimezone($this->_userDateTimeZone);
        
            $this->pdf->Cell(40, 10, $date->format('Y-M-d H:i:s'), 0, 0, 'C');
            $this->pdf->Cell(40, 10, esc_html($this->_registrations[$i]->email),0,0,'C');
            $this->pdf->Cell(40,10,$status,0,1,'C');
            $this->pdf->Cell(80, 10, 'Name: ' . esc_html($this->_registrations[$i]->first_name) . ' ' . esc_html($this->_registrations[$i]->last_name), 0, 1);
        
            if($status =='Approved') {
                $date = new DateTime($this->_registrations[$i]->booking_confirm_date, $this->_UTCDateTime);
                $date->setTimezone($this->_userDateTimeZone);
                $this->pdf->Cell(80, 10,'Approve date: ' . $date->format('Y-M-d H:i:s'), 0, 1);
            }
        
            for($j=0; $j < $customFieldsLength; $j++) {
                $this->pdf->Cell(40, 10, $this->customFieldsWithValues($this->_customFields[$j]['label'], $registrantCustomData), 0, 1);
            }
        
            $this->pdf->Ln(10);
        }
            
        $this->pdf->Output($this->fileName .'.pdf', 'I');	
    }
}