<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

//===========
	/* Data coming from registration view. Someone wants to book a seat/seats */
//===========

class SeatregSubmitBookings extends SeatregBooking {
	public $response; //response object. 
	protected $_bookerEmail; //confirm email is send to this address
	protected $_submittedPassword;  //user submitted password
	protected $_bookingId; //id for booking
	protected $_isValid = true;
	
    public function __construct($code, $resp){
    	$this->_registrationCode = $code;
        $this->response = $resp;

      	$this->getRegistrationAndOptions();
    }

    public function validateBookingData($firstname, $lastname, $email, $seatID, $seatNr, $emailToSend, $code, $pw, $customFields, $roomUUID, $passwords) {
		global $wpdb;
		global $seatreg_db_table_names;

    	$this->_bookerEmail = $emailToSend;
        $this->_submittedPassword = $pw;
		$this->_seatPasswords = json_decode(stripslashes_deep($passwords));
    
		$customFields = stripslashes_deep($customFields);

		//custom fields validation
		$customFieldValidation = SeatregDataValidation::validateBookingCustomFields($customFields, $this->_maxSeats, $this->_createdCustomFields);
		
		if( !$customFieldValidation->valid ) {
			$this->response->setValidationError( $customFieldValidation->errorMessage );

			return false;
		}

		$bookings = [];
		$customFieldData = json_decode( $customFields );

    	foreach ($firstname as $key => $value) {

			//default field validation
			$defaultFieldValidation = SeatregDataValidation::validateDefaultInputOnBookingSubmit($value, $lastname[$key], $email[$key]);

			if( !$defaultFieldValidation->valid ) {
				$this->response->setValidationError( $defaultFieldValidation->errorMessage );
	
				return false;
			}

			//booking data validation
			$bookingDataValidation = SeatregDataValidation::validateBookingData($seatID[$key], $seatNr[$key], $roomUUID[$key]);

			if( !$bookingDataValidation->valid ) {
				$this->response->setValidationError( $bookingDataValidation->errorMessage );
	
				return false;
			}


    		$booking = new stdClass();
    		$booking->firstname = sanitize_text_field($value);
    		$booking->lastname = sanitize_text_field($lastname[$key]);
    		$booking->email = sanitize_email($email[$key]);
    		$booking->seat_id = sanitize_text_field($seatID[$key]);
    		$booking->seat_nr = sanitize_text_field($seatNr[$key]);
			$booking->room_uuid = sanitize_text_field($roomUUID[$key]);
    		$booking->custom_field = $customFieldData[$key];

    		$bookings[] = $booking;
		}
		$registration = SeatregRegistrationRepository::getRegistrationByCode($code);
		$roomData = json_decode($registration->registration_layout)->roomData;
		
		foreach ($bookings as $booking) {
			$booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($roomData, $booking->room_uuid);
		}

		$this->_bookings = $bookings;
		
        return true;
    }

	public function validateBooking() {
		//password check if needed
		if($this->_registrationPassword != null) {
			if($this->_registrationPassword != $this->_submittedPassword) {
				//registration password and user submitted passwords are not the same
				$this->response->setError(esc_html__('Error. Password mismatch!', 'seatreg'));
				
				return;
			}
		}

		//1.step
		//Selected seat limit check
		if(!$this->seatsLimitCheck()) {
			$this->response->setError(esc_html__('Error. Seat limit exceeded', 'seatreg'));

			return;
		}

		//2.step
		$this->isSeperateSeats();

		if(!$this->_isValid) {
			$this->response->setError(esc_html__('Error. Dublicated seats', 'seatreg'));

			return;
		}

		//3.step
		//seat room, id, nr exists check
		$seatsStatusCheck = $this->doSeatsExistInRegistrationLayoutCheck();
		if($seatsStatusCheck != 'ok') {
			$this->response->setError($seatsStatusCheck);

			return;
		}

		//4.step. Email check if needed
		if($this->_gmailNeeded) {
			$gmailReg = '/^[a-z0-9](\.?[a-z0-9]){2,}@g(oogle)?mail\.com$/';

			if(!preg_match($gmailReg, $this->_bookerEmail)) {
				$this->response->setError(esc_html__('Gmail needed!', 'seatreg'));

				return;
			}
		}

		//5.step. Time check. is registration open.
		if ($this->_isRegistrationOpen == false) {
			$this->response->setError(esc_html__('Registration is closed', 'seatreg'));

			return;
		}

		$registrationTime = seatreg_registration_time_status($this->_registrationStartTimestamp, $this->_registrationEndTimestamp);
		if($registrationTime != 'run') {
			$this->response->setError(esc_html__('Registration is not open', 'seatreg'));

			return;
		}

		//6.step. Check if seat/seats are allready taken
		$bookStatus = $this->isAllSelectedSeatsOpen(); 
		if($bookStatus != 'ok') {
			$this->response->setError($bookStatus);

			return;
		}

		//7.step. Check if seat/seats are locked
		$lockStatus = $this->seatLockCheck();
		if($lockStatus != 'ok') {
			$this->response->setError($lockStatus);

			return;
		}

		//8.step. seat/seats password check
		$passwordStatus = $this->seatPasswordCheck();
		if($passwordStatus != 'ok') {
			$this->response->setError($passwordStatus);

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
				array_push($seatIds, $this->_bookings[$i]->seat_id);
			}else {
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
			$currentTimeStamp = time();
			$registrationConfirmDate = null;
			$seatsString = $this->generateSeatString();
			$bookingCheckURL = seatreg_get_registration_status_url($this->_registrationCode, $this->_bookingId);

			if(!$this->_requireBookingEmailConfirm) {
				$bookingStatus = $this->_insertState;
			}
			
			if($this->_insertState == 2) {
				$registrationConfirmDate = $currentTimeStamp;
			}
	 
			for($i = 0; $i < $dataLength; $i++) {
				$wpdb->insert( 
					$seatreg_db_table_names->table_seatreg_bookings, 
					array(
						'registration_code' => $this->_registrationCode, 
						'first_name' => $this->_bookings[$i]->firstname, 
						'last_name' => $this->_bookings[$i]->lastname,
						'email' => $this->_bookings[$i]->email,
						'seat_id' => $this->_bookings[$i]->seat_id,
						'seat_nr' => $this->_bookings[$i]->seat_nr,
						'room_uuid' => $this->_bookings[$i]->room_uuid,
						'conf_code' => $confCode, 
						'custom_field_data' => json_encode($this->_bookings[$i]->custom_field, JSON_UNESCAPED_UNICODE),
						'booking_id' => $this->_bookingId,
						'status' => $bookingStatus,
						'booking_date' => $currentTimeStamp,
						'booking_confirm_date' => $registrationConfirmDate,
						'booker_email' => $this->_bookerEmail,
						'seat_passwords' => json_encode($this->_seatPasswords)
					), 
					'%s'	
				);
			}
			seatreg_add_activity_log('booking', $this->_bookingId, 'Booking inserted to database', false);

			if($this->_requireBookingEmailConfirm) {
				//send email with the confirm link
				$emailVerificationMailSent = seatreg_sent_email_verification_email($confCode, $this->_bookerEmail, $this->_registrationName, $this->_emailVerificationTemplate);

				if($emailVerificationMailSent) {
					seatreg_add_activity_log('booking', $this->_bookingId, 'Booking email verification sent', false);
					$this->response->setText('mail');
				}else {
					seatreg_add_activity_log('booking', $this->_bookingId, 'Booking email verification sending failed', false);
					$this->response->setError(esc_html__('Oops.. the system encountered a problem while sending out confirmation email. Please notify the site administrator.', 'seatreg'));
				}
				
			}else {
				if($this->_sendNewBookingNotificationEmail) {
					seatreg_send_booking_notification_email($this->_registrationCode, $this->_bookingId,  $this->_sendNewBookingNotificationEmail);
				}
				if($this->_insertState === SEATREG_BOOKING_PENDING) {
					seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to pending state by the system (No email verification)', false);

					$peningBookingEmailSent = seatreg_send_pending_booking_email($this->_registrationName, $this->_bookerEmail, $bookingCheckURL, $this->_pendingBookingTemplate);

					if($peningBookingEmailSent) {
						seatreg_add_activity_log('booking', $this->_bookingId, 'Pending booking email sent', false);
						$this->response->setText('bookings-confirmed-status-1');
						$this->response->setData($bookingCheckURL);
					}else {
						seatreg_add_activity_log('booking', $this->_bookingId, 'Pending booking email sending failed', false);
						$this->response->setError(esc_html__('Oops.. the system encountered a problem while sending out booking email. Please notify the site administrator.', 'seatreg'));
					}
					
				}else if($this->_insertState === SEATREG_BOOKING_APPROVED) {
					seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to approved state by the system (No email verification)', false);

					if($this->_sendApprovedBookingEmail) {
						$approvedEmailSent = seatreg_send_approved_booking_email($this->_bookingId, $this->_registrationCode, $this->_approvedBookingTemplate);

						if($approvedEmailSent) {
							$this->response->setText('bookings-confirmed-status-2');
							$this->response->setData($bookingCheckURL);
						}else {
							seatreg_add_activity_log('booking', $this->_bookingId, 'Approved booking email sending failed', false);
							$this->response->setError(esc_html__('Oops.. the system encountered a problem while sending out booking email. Please notify the site administrator.', 'seatreg'));
						}
					}else {
						$this->response->setText('bookings-confirmed-status-2');
						$this->response->setData($bookingCheckURL);
					}
				}
			}	
		}
	}
}