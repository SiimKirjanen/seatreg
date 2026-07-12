<?php
if(!defined('ABSPATH')) exit;

require_once( SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/tfpdf/tfpdf.php' );

class SeatregBookingPDF extends tFPDF {
    private $_bookingId;
    private $_bookings;
    private $_bookingData;
    private $_payment;
    private $_usingSeats;
    private $_customFields;
    private $_roomData;
    private $_logoPath;
    private $_logoPosition;

    public function __construct($bookingId, $bookings, $bookingData) {
        parent::__construct();
        $this->_bookingId = $bookingId;
        $this->_bookings = $bookings;
        $this->_bookingData = $bookingData;

        $this->setUp();
    }

    function Header() {
        if( $this->_logoPath && in_array($this->_logoPosition, array('top-left', 'top-right'), true) ) {
            $this->renderLogo();
        }

        // Push the header text below the logo so they don't overlap when the logo is top-left.
        if( $this->_logoPath && $this->_logoPosition === 'top-left' ) {
            $this->SetY( $this->GetY() + $this->getLogoHeight() + 4 );
        }

        $this->SetFont('DejaVu','',14);
        $this->Cell(30, 0, $this->_bookingData->registration_name , 0, 1, 'L');
        $this->Ln(6);
        $this->SetFont('DejaVu','',10);
        /* translators: %s: Date and time when the PDF file was generated */
        $this->Cell(30, 0, sprintf( esc_html__('File generated: %s', 'seatreg'), SeatregTimeService::getDateStringFromUnix( time() ) ), 0, 1, 'L');
        $this->Ln(10);
    }

    function Footer() {
        if( $this->_logoPath && in_array($this->_logoPosition, array('bottom-left', 'bottom-right'), true) ) {
            $this->renderLogo();
        }
    }

    public function setUp() {
        $this->SetAuthor('SeatReg WordPress');
        $this->AddFont('DejaVu','','DejaVuSans.ttf', true);
        // Resolve the logo before AddPage() since Header()/Footer() fire during it.
        $this->setUpLogo();
        $this->AliasNbPages();
        $this->AddPage();
        $this->SetFont('DejaVu','',10);

       if( $this->_bookingData->registration_layout !== null ) {
            $this->_roomData = json_decode( $this->_bookingData->registration_layout )->roomData;
    
            foreach($this->_bookings as $booking) {
                $booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($this->_roomData, $booking->room_uuid);
            }
        }
        
        $this->_payment = SeatregPaymentRepository::getPaymentByBookingId( $this->_bookingId );
        $this->_customFields = ($this->_bookingData->custom_fields !== null) ? json_decode( $this->_bookingData->custom_fields, true ) : [];
    }

    protected function setUpLogo() {
        $allowedPositions = array('top-left', 'top-right', 'bottom-left', 'bottom-right');

        if( empty($this->_bookingData->booking_pdf_logo_id) || !in_array($this->_bookingData->booking_pdf_logo_position, $allowedPositions, true) ) {
            return;
        }

        $path = get_attached_file( $this->_bookingData->booking_pdf_logo_id );

        if( !$path || !file_exists($path) ) {
            return;
        }

        // tFPDF can only render raster images.
        $extension = strtolower( pathinfo($path, PATHINFO_EXTENSION) );
        if( !in_array($extension, array('jpg', 'jpeg', 'png', 'gif'), true) ) {
            return;
        }

        $this->_logoPath = $path;
        $this->_logoPosition = $this->_bookingData->booking_pdf_logo_position;
    }

    // The logo size is defined by the source image itself (pixels converted to mm),
    // so it can be controlled by picking a different image/resolution in the media library.
    // Returns [width, height] in mm.
    protected function getLogoDimensions() {
        $dpi = 96;
        $size = @getimagesize( $this->_logoPath );

        if( !$size || empty($size[0]) || empty($size[1]) ) {
            return array(0, 0);
        }

        $width = $size[0] / $dpi * 25.4;
        $height = $size[1] / $dpi * 25.4;

        // Safety clamp: never let the logo exceed the usable page area (keep aspect ratio).
        $maxWidth = $this->w - $this->lMargin - $this->rMargin;
        $maxHeight = $this->h - $this->tMargin - $this->bMargin;
        $ratio = min( 1, $maxWidth / $width, $maxHeight / $height );

        return array( $width * $ratio, $height * $ratio );
    }

    protected function getLogoHeight() {
        list( , $height ) = $this->getLogoDimensions();

        return $height;
    }

    protected function renderLogo() {
        list( $width, $height ) = $this->getLogoDimensions();

        // A Cell with height 0 positions text by its baseline, so the header title's
        // glyphs sit ~0.4 * font-size above the top margin. Nudge top logos up by the
        // same amount so their top edge lines up with the title instead of looking lower.
        $topAlign = ( 14 / $this->k ) * 0.4;

        switch( $this->_logoPosition ) {
            case 'top-left':
                $x = $this->lMargin;
                $y = $this->tMargin - $topAlign;
                break;
            case 'top-right':
                $x = $this->w - $this->rMargin - $width;
                $y = $this->tMargin - $topAlign;
                break;
            case 'bottom-left':
                $x = $this->lMargin;
                $y = $this->h - $this->tMargin - $height;
                break;
            case 'bottom-right':
                $x = $this->w - $this->rMargin - $width;
                $y = $this->h - $this->tMargin - $height;
                break;
            default:
                return;
        }

        $this->Image( $this->_logoPath, $x, $y, $width, $height );
    }

    public function printPDF() {

        foreach( $this->_bookings as $booking ) {
            $placeNumberText = $this->_bookingData->using_seats ? esc_html__('Seat number', 'seatreg') : esc_html__('Place number', 'seatreg');
            $bookingDate = SeatregTimeService::getDateStringFromUnix($booking->booking_date);
            $status = $this->getStatus($booking->status);
            $paymentStatus = $this->_payment->payment_status ?? null;
            $registrantCustomData = json_decode($booking->custom_field_data, true);
            $seatPrice = SeatregLayoutService::getSeatPriceFromLayout($booking, $this->_roomData);

            $this->Cell(20, 6, $placeNumberText . ': ' . esc_html($booking->seat_nr), 0, 1, 'L');
            $this->Cell(20, 6, esc_html__('Room name', 'seatreg') . ': ' . esc_html($booking->room_name), 0, 1, 'L');
            $this->Cell(20, 6, esc_html__('Name', 'seatreg') . ': ' . esc_html($booking->first_name) . ' ' . esc_html($booking->last_name), 0, 1, 'L');
            $this->Cell(20, 6, esc_html__('Email', 'seatreg') . ': ' . $booking->email, 0, 1, 'L');
            $this->Cell(20, 6, esc_html__('Booking time', 'seatreg') . ': ' . $bookingDate, 0, 1, 'L');

            if( $seatPrice ) {
                $priceDescription = $seatPrice->description ? '('. $seatPrice->description . ')' : '';
                $this->Cell(20, 6, esc_html__('Price', 'seatreg') . ': ' . $seatPrice->price . ' ' . $this->_bookingData->paypal_currency_code . ' ' . $priceDescription, 0, 1, 'L');
            }

            if( $booking->calendar_date ) {
                $this->Cell(20, 6, esc_html__('Calendar date', 'seatreg') . ': ' . $booking->calendar_date, 0, 1, 'L');
            }

            $this->Cell(20, 6, esc_html__('Booking id', 'seatreg') . ': ' . esc_html($booking->booking_id), 0, 1, 'L');
            $this->Cell(20, 6, esc_html__('Booking status', 'seatreg') . ': ' . $status, 0, 1, 'L');

            if($status =='Approved') {
                $confirmDate = SeatregTimeService::getDateStringFromUnix( $booking->booking_confirm_date );

                $this->Cell(20, 6, esc_html__('Booking approval time', 'seatreg') . ': ' . $confirmDate, 0, 1, 'L');
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

        if( $this->_bookingData->send_approved_booking_email_qr_code ) {
            $qrContent = SeatregRegQRCodeService::getQRCodeContent( $this->_bookingId, $this->_bookingData->registration_code, $this->_bookingData->send_approved_booking_email_qr_code );
            SeatregRegQRCodeService::generateQRCodeImage( $qrContent, $this->_bookingId );
            
            $this->image(SEATREG_TEMP_FOLDER_DIR. '/' . $this->_bookingId . '.png');
        }
        
        $this->Output($this->_bookingData->registration_name . '_' .  $this->_bookingId . '.pdf', 'I');	
    }

    protected function getStatus($status) {
        return $status === '2'
            ? esc_html__('Approved', 'seatreg')
            : esc_html__('Pending', 'seatreg');
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