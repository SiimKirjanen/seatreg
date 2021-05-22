<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
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
        //$this->Image(SEATREG_PLUGIN_FOLDER_DIR. 'img/seatreg_logo.png',9,5,30);
        $this->SetFont('Arial','',10);   
        $this->Cell(30, 0, $this->currentDate->format('Y-M-d H:i:s') . ' ' . $this->title, 0, 1, 'L');
        // Line break
        $this->Ln(10);
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
        $this->fileName = esc_html($this->_registrationName . ' ' . $this->_currentDateTime->format('Y-M-d'));

        $this->pdf = new SeatregPDF($this->_registrationName, $this->_currentDateTime);
        $this->pdf->SetTitle( $this->fileName );
        $this->pdf->SetAuthor('SeatReg WordPress');
        $this->pdf->AddFont('DejaVu','','DejaVuSans.ttf', true);
        $this->pdf->AliasNbPages();
        $this->pdf->AddPage();
        $this->pdf->SetFont('DejaVu','',10);
    }
    public function printPDF() {
        $registrationsLenght = count($this->_registrations);
        $customFieldsLength = count($this->_customFields);

        foreach ($this->_registrations as $registration) {
            $registrantCustomData = json_decode($registration->custom_field_data, true);
            $status = $this->getStatus($registration->status);
            $bookingDate = $this->getBookingDateTime($registration->booking_date);

            $this->pdf->Cell(20, 6, esc_html__('Seat number:', 'seatreg') . ' ' . esc_html($registration->seat_nr), 0, 1, 'L');
            $this->pdf->Cell(20, 6, esc_html__('Room name:', 'seatreg') . ' ' . esc_html($registration->room_name), 0, 1, 'L');
            $this->pdf->Cell(20, 6, esc_html__('Name:', 'seatreg') . ' ' . esc_html($registration->first_name) . ' ' . esc_html($registration->last_name), 0, 1, 'L');
            $this->pdf->Cell(20, 6, esc_html__('Email:', 'seatreg') . ' ' . $registration->email, 0, 1, 'L');
            $this->pdf->Cell(20, 6, esc_html__('Registration date:', 'seatreg') . ' ' . $bookingDate->format('Y-M-d H:i:s'), 0, 1, 'L');
            $this->pdf->Cell(20, 6, esc_html__('Status:', 'seatreg') . ' ' . $status, 0, 1, 'L');

            if($status =='Approved') {
                $confirmDate = $this->getBookingDateTime($registration->booking_confirm_date);
                $this->pdf->Cell(20, 6, 'Confirmation date: ' . $confirmDate->format('Y-M-d H:i:s'), 0, 1, 'L');
            }
                        
            foreach ($this->_customFields as $customField) {
                $this->pdf->Cell(20, 6, $this->customFieldsWithValues($customField, $registrantCustomData), 0, 1);
            }
        
            $this->pdf->Ln(10);
        }
            
        $this->pdf->Output($this->fileName .'.pdf', 'I');	
    }
}