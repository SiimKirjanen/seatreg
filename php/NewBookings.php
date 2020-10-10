<?php

//===========
	/*data coming from registration. Someone wants to book a seat*/
//===========

require_once('Booking.php');
require_once('emails.php');
require_once('util/registration_time_status.php');
require_once('util/session_captcha.php');

class NewBookings extends Booking {
	public $response; //response object. 
	protected $_isValid = true;
	protected $_bookerEmail; //confirm email is send to this address
	protected $_bookings; //seat bookings 
	protected $_registrationLayout;
	protected $_registrationCode;
	protected $_gmailNeeded = false;  //require gmail address from registrants
	protected $_registrationStartTimestamp;
	protected $_registrationEndTimestamp; //when registration ends
	protected $_submittedPassword;  //user submitted password
	protected $_registrationPassword = null;  //registration password if set. null default
	protected $_isRegistrationOpen = true; //is registration open
	protected $_bookingId; //id for booking
	protected $_maxSeats = 1;  //how many seats per booking can be booked
	
    public function __construct( $code, $resp){
    	$this->_registrationCode = $code;
        $this->response = $resp;

      	$this->getRegistrationAndOptions();
    }

    public function validateBookingData($firstname, $lastname, $email, $seatID, $seatNr, $seatRoom, $emailToSend, $code, $pw, $customFields) {
    	$this->_bookerEmail = $emailToSend;
        $this->_submittedPassword = $pw;
    
		$customFields = stripslashes_deep($customFields);
		
		if( !$this->seatreg_validate_custom_fields( $customFields ) ) {
			$this->response->setError( __('Custom field validation error','seatreg') );
			return false;
		}

		$bookings = [];
		$customFieldData = json_decode( $customFields );

    	foreach ($firstname as $key => $value) {
    		$booking = new stdClass();
    		$booking->firstname = $value;
    		$booking->lastname = $lastname[$key];
    		$booking->email = $email[$key];
    		$booking->seat_id = $seatID[$key];
    		$booking->seat_nr = $seatNr[$key];
    		$booking->room_name = $seatRoom[$key];
    		$booking->custom_field = $customFieldData[$key];

    		$bookings[] = $booking;
    	}
        $this->_bookings = $bookings;

        return true;
    }

    public function seatreg_validate_custom_fields($customFields) {
		$customFieldsReg = '/^\[(\[({"label":[0-9a-zA-ZÜÕÖÄüõöä\s@."]{1,102},"value":[0-9a-zA-ZÜÕÖÄüõöä\s@."-]{1,52}},?){0,6}\],?){1,3}\]$/';

		if( !preg_match($customFieldsReg, $customFields) ) {
			return false;
		}

		return true;
	}
	public function validateBooking() {

		//1.step
		$this->isSeperateSeats();

		if(!$this->_isValid) {
			$this->response->setError(__('Error. Dublicated seats', 'seatreg'));

			return;
		}

		//password check if needed
		if($this->_registrationPassword != null) {
			if($this->_registrationPassword != $this->_submittedPassword) {
				//registration password and user submitted passwords are not the same
				$this->response->setError(__('Error. Password mismatch!', 'seatreg'));
				
				return;
			}
		}

		//2.step
		//seat room, id, nr and is availvable check.
		$seatsStatusCheck = $this->doSeatsExistInRegistrationLayoutCheck();
		if($seatsStatusCheck != 'ok') {
			$this->response->setError($seatsStatusCheck);
			return;
		}

		//3.step. Email check if needed
		if($this->_gmailNeeded) {
			$gmailReg = '/^[a-z0-9](\.?[a-z0-9]){2,}@g(oogle)?mail\.com$/';

			if(!preg_match($gmailReg, $this->_bookerEmail)) {
				$this->response->setError(__('Gmail needed!', 'seatreg'));
				return;
			}
		}

		//4.step. Time check. is registration open.
		if ($this->_isRegistrationOpen == false) {
			$this->response->setError(__('Registration is closed', 'seatreg'));
			return;
		}

		$registrationTime = seatreg_registration_time_status($this->_registrationStartTimestamp, $this->_registrationEndTimestamp);
		if($registrationTime != 'run') {
			$this->response->setError(__('Registration is not open', 'seatreg'));
			return;
		}

		//5.step. Check if seat/seats are allready taken
		$bookStatus = $this->isAllSelectedSeatsOpen(); 
		if($bookStatus != 'ok') {
			$this->response->setError($bookStatus);
			return;
		}

		$this->insertRegistration();
	}

	public function getStatus() {
		return $this->_isValid;
	}

	private function isSeperateSeats() {
		//check so each seat is different. Prevents dublicate booking on same seat
		$seatIds = array();
		$dataLen = count($this->_bookings);

		for($i = 0; $i < $dataLen; $i++) {
			if(!in_array($this->_bookings[$i]->seat_id, $seatIds)) {
				//print_r($seatIds);
				//echo $this->_data[$i][0], ' not in array. insert it--------';
				array_push($seatIds, $this->_bookings[$i]->seat_id);
			}else {
				//echo $this->_data[$i][0], ' is already in. return false--------';
				$this->_isValid = false;
				break;
			}
		}
	}

	public function insertRegistration() {
		if($this->_isValid) {
			global $wpdb;
			global $seatreg_db_table_names;

			$dataLength = count($this->_bookings);
			$inserted = true;
			$bookingStatus = 0;
			$confCode = sha1(mt_rand(10000,99999).time().$this->_bookerEmail);
			$this->_bookingId = sha1(mt_rand(10000,99999).time().$this->_bookerEmail);
			$registrationConfirmDate = null;
			$seatsString = $this->generateSeatString();

			if(!$this->_requireBookingEmailConfirm) {
				$bookingStatus = $this->_insertState;
				$registrationConfirmDate = current_time( 'mysql' );
			}
	 
			for($i = 0; $i < $dataLength; $i++) {
				$wpdb->insert( 
					$seatreg_db_table_names->table_seatreg_bookings, 
					array(
						'seatreg_code' => $this->_registrationCode, 
						'first_name' => $this->_bookings[$i]->firstname, 
						'last_name' => $this->_bookings[$i]->lastname,
						'email' => $this->_bookings[$i]->email,
						'seat_id' => $this->_bookings[$i]->seat_id,
						'seat_nr' => $this->_bookings[$i]->seat_nr,
						'room_name' => $this->_bookings[$i]->room_name,
						'conf_code' => $confCode, 
						'custom_field_data' => json_encode($this->_bookings[$i]->custom_field, JSON_UNESCAPED_UNICODE),
						'booking_id' => $this->_bookingId,
						'status' => $bookingStatus,
						'registration_confirm_date' => $registrationConfirmDate
					), 
					'%s'	
				);
			}

			if($this->_requireBookingEmailConfirm) {
				seatreg_change_captcha(3);
				$confirmationURL = WP_PLUGIN_URL . '/seatreg_wordpress/php/booking_confirm.php?confirmation-code='. $confCode;
				$bookingCheckURL = WP_PLUGIN_URL . '/seatreg_wordpress/php/booking_check.php?registration=' . $this->_registrationCode . '&id=' . $this->_bookingId;


				$message = __('Your selected seats are', 'seatreg') . ': <br/><br/>' . $seatsString . '
							<p>' . __('Click on the link below to confirm your booking', 'seatreg') . '</p>
							<a href="' .  $confirmationURL .'" >'. $confirmationURL .'</a><br/>
							('. __('If you can\'t click then copy and paste it into your web browser', 'seatreg') . ')<br/><br/>
							' .__('After confirmation you can look your booking at', 'seatreg') . '<br> <a href="'. $bookingCheckURL .'" >'. $bookingCheckURL .'</a>';

				$mailSent = wp_mail($this->_bookerEmail, __('Booking confirmation', 'seatreg'), $message, array(
					"Content-type: text/html"
				));

				if($mailSent) {
					$this->response->setText('mail');
					if($this->_sendNewBookingNotificationEmail) {
						seatreg_send_booking_notification_email($this->_registrationName, $seatsString, $this->_sendNewBookingNotificationEmail);
					}
					
				}else {
					$this->response->setError(__('Oops.. the system encountered a problem while sending out confirmation email', 'seatreg'));
				}
				
			}else {
				if($this->_sendNewBookingNotificationEmail) {
					seatreg_send_booking_notification_email($this->_registrationName, $seatsString, $this->_sendNewBookingNotificationEmail);
				}
				
				$this->response->setText('bookings-confirmed');
			}	
		}
	}
}