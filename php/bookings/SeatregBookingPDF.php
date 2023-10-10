<?php
require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/tfpdf/tfpdf.php' );

class SeatregBookingPDF extends tFPDF {
    private $_bookingId;
    private $_bookings;
    private $_bookingData;
    private $_payment;
    private $_usingSeats;
    private $_customFields;

    public function __construct($bookingId, $bookings, $bookingData) {
        parent::__construct();
        $this->_bookingId = $bookingId;
        $this->_bookings = $bookings;
        $this->_bookingData = $bookingData;

        $this->setUp();
    }

    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Cell(30, 0, $this->_bookingData->registration_name , 0, 1, 'L');
        $this->Ln(6);
        $this->SetFont('Arial','',10);   
        $this->Cell(30, 0, $this->getCurrenctDate() , 0, 1, 'L');
        $this->Ln(10);
    }

    public function setUp() {
        $this->SetTitle( 'Test' );
        $this->SetAuthor('SeatReg WordPress');
        $this->AddFont('DejaVu','','DejaVuSans.ttf', true);
        $this->AliasNbPages();
        $this->AddPage();
        $this->SetFont('DejaVu','',10);

       if( $this->_bookingData->registration_layout !== null ) {
            $roomData = json_decode( $this->_bookingData->registration_layout )->roomData;
    
            foreach($this->_bookings as $booking) {
                $booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($roomData, $booking->room_uuid);
            }
        }
        
        $this->_payment = SeatregPaymentRepository::getPaymentByBookingId( $this->_bookingId );
        $this->_customFields = ($this->_bookingData->custom_fields !== null) ? json_decode( $this->_bookingData->custom_fields, true ) : [];
    }

    public function getCurrenctDate() {
        return date(get_option('date_format'));
    }

    public function printPDF() {

        foreach( $this->_bookings as $booking ) {
            $placeNumberText = $this->_bookingData->using_seats ? esc_html__('Seat number', 'seatreg') : esc_html__('Place number', 'seatreg');
            $bookingDate = $this->getBookingDate($booking->booking_date);
            $status = $this->getStatus($booking->status);
            $paymentStatus = $this->_payment->payment_status ?? null;
            $registrantCustomData = json_decode($booking->custom_field_data, true);

            $this->Cell(20, 6, $placeNumberText . ': ' . esc_html($booking->seat_nr), 0, 1, 'L');
            $this->Cell(20, 6, esc_html__('Room name', 'seatreg') . ': ' . esc_html($booking->room_name), 0, 1, 'L');
            $this->Cell(20, 6, esc_html__('Name', 'seatreg') . ': ' . esc_html($booking->first_name) . ' ' . esc_html($booking->last_name), 0, 1, 'L');
            $this->Cell(20, 6, esc_html__('Email', 'seatreg') . ': ' . $booking->email, 0, 1, 'L');
            $this->Cell(20, 6, esc_html__('Registration date', 'seatreg') . ': ' . $bookingDate, 0, 1, 'L');

            if( $booking->calendar_date ) {
                $this->Cell(20, 6, esc_html__('Calendar date', 'seatreg') . ': ' . $booking->calendar_date, 0, 1, 'L');
            }

            $this->Cell(20, 6, esc_html__('Booking id', 'seatreg') . ': ' . esc_html($booking->booking_id), 0, 1, 'L');
            $this->Cell(20, 6, esc_html__('Booking status', 'seatreg') . ': ' . $status, 0, 1, 'L');

            if($status =='Approved') {
                $confirmDate = $this->getBookingDate( $booking->booking_confirm_date );

                $this->Cell(20, 6, esc_html__('Booking approval date', 'seatreg') . ': ' . $confirmDate, 0, 1, 'L');
            }

            if( $paymentStatus !== null ) {
                $this->Cell(20, 6, esc_html__('Payment status', 'seatreg') . ': ' . $paymentStatus, 0, 1, 'L');

                if( $paymentStatus == SEATREG_PAYMENT_COMPLETED ) {
                    $this->Cell(20, 6, esc_html__('Payment received', 'seatreg') . ': ' . $this->_payment->payment_total_price . ' ' . $this->_payment->payment_currency, 0, 1, 'L');
                }
            }

            foreach ( $this->_customFields as $customField ) {
                $this->Cell(20, 6, $this->customFieldsWithValues($customField, $registrantCustomData), 0, 1);
            }
            
            $this->Ln(10);
        }

        $this->Output($this->_bookingData->registration_name . '_' .  $this->_bookingId . '.pdf', 'I');	
    }

    protected function getBookingDate($timestamp) {
        return date('M j Y h:i e', $timestamp);
    }

    protected function getStatus($status) {
        return $status === "2" ? "Approved" : "Pending";
    }

    protected function customFieldsWithValues($customField, $customData) {
        $cust_len = count(is_array($customData) ? $customData : []);
        $foundIt = false;
	    $string = $customField['label'] . ': ';

        for($k = 0; $k < $cust_len; $k++) {
            if($customData[$k]['label'] == $customField['label'] ) {

                if($customField['type'] === 'check') {
                    if($customData[$k]['value'] === '1') {
                        $string .= esc_html__('Checked', 'seatreg');
                    }else if($customData[$k]['value'] === '0') {
                        $string .= esc_html__('Unchecked', 'seatreg');
                    }
                }else {
                    $string .= esc_html($customData[$k]['value']);
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
}