<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/bookings/SeatregBookingsFile.php');
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/tfpdf/tfpdf.php' );

class SeatregPDF extends tFPDF {
    private $title = null;
    private $currentDate = null;
    private $currentTimestamp = null;
    
    public function __construct($title, $currentTimestamp) {
        parent::__construct();
        $this->title = $title;
        $this->currentTimestamp = $currentTimestamp;
    }
    
    function Header() {
        $this->SetFont('DejaVu','',14);
        $this->Cell(30, 0, $this->title, 0, 1, 'L');
        $this->Ln(6);
        $this->SetFont('DejaVu','',10);   
        $this->Cell(30, 0, sprintf( esc_html('File generated: %s', 'seatreg'), SeatregTimeService::getDateStringFromUnix($this->currentTimestamp) ), 0, 1, 'L');
        $this->Ln(10);
    }

    // Page footer
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0, 10, esc_html__('Page', 'seatreg') . ' ' . $this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

class SeatregBookingsPDF extends SeatregBookingsFile {
    private $pdf = null;
    private $fileName = null;

    public function __construct($showPending, $showConfirmed, $separateFirstandLastName, $registrationCode, $calendarDate) {
        parent::__construct($showPending, $showConfirmed, $separateFirstandLastName, $registrationCode, $calendarDate);

        $this->setupPDF();
	}
    private function setupPDF() {
        $this->fileName = esc_html( $this->_registrationName . ' ' . $this->getFileName() );

        $this->pdf = new SeatregPDF($this->_registrationName, $this->_currentTimestamp);
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
            $bookingDate = SeatregTimeService::getDateStringFromUnix($registration->booking_date);
            $placeNumberText = $this->_usingSeats ? esc_html__('Seat number', 'seatreg') : esc_html__('Place number', 'seatreg');
            $seatPrice = SeatregLayoutService::getSeatPriceFromLayout($registration, $this->_roomData);
            $usedCouponString = SeatregCouponService::getAppliedCouponString(json_decode($registration->applied_coupon) ?? null);

            $this->pdf->Cell(20, 6, $placeNumberText . ': ' . esc_html($registration->seat_nr), 0, 1, 'L');
            $this->pdf->Cell(20, 6, esc_html__('Room name', 'seatreg') . ': ' . esc_html($registration->room_name), 0, 1, 'L');
            if ( $this->_separateFirstandLastName ) {
                $this->pdf->Cell(20, 6, esc_html__('First name', 'seatreg') . ': ' . esc_html($registration->first_name), 0, 1, 'L');
                $this->pdf->Cell(20, 6, esc_html__('Last name', 'seatreg') . ': ' . esc_html($registration->last_name), 0, 1, 'L');
            } else {
                $this->pdf->Cell(20, 6, esc_html__('Name', 'seatreg') . ': ' . esc_html($registration->first_name) . ' ' . esc_html($registration->last_name), 0, 1, 'L');
            }
            $this->pdf->Cell(20, 6, esc_html__('Email', 'seatreg') . ': ' . $registration->email, 0, 1, 'L');
            $this->pdf->Cell(20, 6, esc_html__('Booking time', 'seatreg') . ': ' . $bookingDate, 0, 1, 'L');

            if( $seatPrice ) {
                $priceDescription = $seatPrice->description ? '('. $seatPrice->description . ')' : '';
                $this->pdf->Cell(20, 6, esc_html__('Price', 'seatreg') . ': ' . $seatPrice->price . ' ' . $this->_registrationInfo->paypal_currency_code . ' ' . $priceDescription, 0, 1, 'L');
            }

            if($this->_calendarDate) {
                $this->pdf->Cell(20, 6, esc_html__('Calendar date', 'seatreg') . ': ' . $this->_calendarDate, 0, 1, 'L');
            }

            $this->pdf->Cell(20, 6, esc_html__('Booking id', 'seatreg') . ': ' . esc_html($registration->booking_id), 0, 1, 'L');
            $this->pdf->Cell(20, 6, esc_html__('Booking status', 'seatreg') . ': ' . $status, 0, 1, 'L');

            if($status =='Approved') {
                $confirmDate = SeatregTimeService::getDateStringFromUnix( $registration->booking_confirm_date );
                $this->pdf->Cell(20, 6, esc_html__('Booking approval time', 'seatreg') . ': ' . $confirmDate, 0, 1, 'L');
            }

            $this->pdf->Cell(20, 6, esc_html__('Used coupon', 'seatreg') . ': ' . $usedCouponString, 0, 1, 'L');
            
            if($registration->payment_status != null) {
                $this->pdf->Cell(20, 6, esc_html__('Payment status', 'seatreg') . ': ' . $registration->payment_status, 0, 1, 'L');

                if($registration->payment_status == SEATREG_PAYMENT_COMPLETED) {
                    $this->pdf->Cell(20, 6, esc_html__('Payment txn id', 'seatreg') . ': ' . $registration->payment_txn_id, 0, 1, 'L');
                    $this->pdf->Cell(20, 6, esc_html__('Payment received', 'seatreg') . ': ' . $registration->payment_total_price . ' ' . $registration->payment_currency, 0, 1, 'L');
                }
            }

            foreach ($this->_customFields as $customField) {
                $this->pdf->Cell(20, 6, $this->customFieldsWithValues($customField, $registrantCustomData), 0, 1);
            }
        
            $this->pdf->Ln(10);
        }
            
        $this->pdf->Output($this->fileName .'.pdf', 'I');	
    }
}