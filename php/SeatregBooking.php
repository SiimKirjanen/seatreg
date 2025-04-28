<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class SeatregBooking {
	protected $_bookings; //seat bookings 
	protected $_registrationLayout;
	protected $_registrationLayoutFull;
	protected $_registrationCode;
	protected $_valid = true;
	protected $_requireBookingEmailConfirm = true;
	protected $_insertState = 1;  //all bookings will have status = 1 (pending). if 2 then (confirmed)
	protected $_registrationName;
	protected $_sendNewBookingNotificationEmail = null; //send notification to admin that someone has booked a seat
	protected $_sendNewPendingBookingNotificationBookerEmail = null; //send notification to booker that the booking is pending
	protected $_maxSeats = 1;  //how many seats per booking can be booked
	protected $_isRegistrationOpen = true; //is registration open
	protected $_registrationPassword = null;  //registration password if set. null default
	protected $_registrationEndTimestamp; //when registration ends
	protected $_registrationStartTimestamp;
	protected $_gmailNeeded = false;  //require gmail address from registrants
	protected $_createdCustomFields;
	protected $_emailVerificationSubject;
	protected $_emailVerificationTemplate;
	protected $_pendingBookingSubject;
	protected $_pendingBookingTemplate;
	protected $_approvedBookingSubject;
	protected $_approvedBookingTemplate;
	protected $_sendApprovedBookingEmail;
	protected $_seatPasswords; //seat passwords provided by seat registration
	protected $_emailFromAddress = null;
	protected $_bookingSameEmailLimit = null;
	protected $_usingCalendar = false; //is registration calendar mode activated?
	protected $_calendarDates = []; // dates for calendar mode
	protected $_userSelectedCalendarDate = null;
	protected $_registrationStartTime = null;
	protected $_registrationEndTime = null;
	protected $_require_wp_login = null;
	protected $_wp_user_booking_limit = null;
	protected $_wp_user_bookings_seat_limit = null;
	protected $_require_name = true; //require full name from registrants
	
    protected function generateSeatString() {
    	$dataLen = count($this->_bookings);
    	$seatsString = '';

    	for($i = 0; $i < $dataLen; $i++) {
    		$seatsString .= esc_html__('Seat nr', 'seatreg') . ': <b>' . esc_html($this->_bookings[$i]->seat_nr) . '</b> ' . esc_html__('from room', 'seatreg') . ': <b>' . esc_html($this->_bookings[$i]->room_name) . '</b><br/>'; 
		}
		
    	return $seatsString;
    }

    protected function isAllSelectedSeatsOpen($calendarDate = null) {  
		$bookingsLength = count($this->_bookings);
		$bookedBookings = SeatregBookingRepository::getConfirmedAndApprovedBookingsByRegistrationCode($this->_registrationCode, $calendarDate);	
		$bookedBookingsLength = count($bookedBookings);
		$statusReport = 'ok';

		for($i = 0; $i < $bookingsLength; $i++) {
			for($j = 0; $j < $bookedBookingsLength; $j++) {
				if($this->_bookings[$i]->seat_id == $bookedBookings[$j]->seat_id) {
					$statusReport = 'Seat <b>'. esc_html($this->_bookings[$i]->seat_nr) . '</b> in room <b>' . esc_html($this->_bookings[$i]->room_name) . '</b > is already confirmed';

					if( $calendarDate ) {
						$statusReport .= ' for <b>' . $calendarDate . '<b>';
					}
					break 2;
				}
			}
		}

		return $statusReport;
	}

	protected function seatLockCheck() {
		$statusReport = 'ok';

		foreach( $this->_bookings as $booking ) {
			if( SeatregLayoutService::checkIfSeatLocked($this->_registrationLayoutFull, $booking->seat_id) ) {
				$statusReport = sprintf(esc_html__('Seat %s is locked', 'seatreg'),  $booking->seat_nr);

				break;
			}
		}

		return $statusReport;
	}

	protected function seatPasswordCheck() {
		$statusReport = 'ok';

		foreach( $this->_bookings as $booking ) {
			if( SeatregLayoutService::checkIfSeatHasPassword($this->_registrationLayoutFull, $booking->seat_id) ) {
				$enteredSeatPasswords = get_object_vars($this->_seatPasswords);
				$enteredPassword = array_key_exists($booking->seat_id, $enteredSeatPasswords) ? $enteredSeatPasswords[$booking->seat_id] : '';

				if( SeatregLayoutService::getSeatPassword($this->_registrationLayoutFull, $booking->seat_id) !== $enteredPassword ) {
					$statusReport = sprintf(esc_html__('Seat %s password is not correct', 'seatreg'),  $booking->seat_nr);

					break;
				}
			}
		}

		return $statusReport;
	}

	protected function multiPriceUUIDCheck() {
		$statusReport = 'ok';

		foreach( $this->_bookings as $booking ) {
			if( $booking->multi_price_selection ) {
				if( SeatregLayoutService::checkIfMultiPriceUUIDExists($booking, $this->_registrationLayout) === false ) {
					$statusReport = esc_html__('Selected price not found', 'seatreg');
				}
			}
		}

		return $statusReport;
	}

	protected function seatsLimitCheck() {
		if(count($this->_bookings) > $this->_maxSeats) {

			return false;
		}

		return true;
	}

	protected function calendarDateValidation($bookingSelectedDate) {
		$statusReport = 'ok';
		$hasCalendarDates = count($this->_calendarDates) >= 1;

		if( !in_array($bookingSelectedDate, $this->_calendarDates) && $hasCalendarDates ) {
			$statusReport = esc_html__('Selected date not available', 'seatreg');	
		}

		return $statusReport;
	}

	protected function calendarDatePastDateCheck($bookingSelectedDate) {
		$statusReport = 'ok';

		$currentTimeStamp = strtotime( date(CALENDAR_DATE_FORMAT) );
		$bookingTimeStamp = strtotime( $bookingSelectedDate );

		if( $bookingTimeStamp < $currentTimeStamp ) {
			$statusReport = esc_html__('Selected date is in the past', 'seatreg');
		}

		return $statusReport;
	}

	protected function calendarDateFormatCheck($bookingSelectedDate) {
		$statusReport = 'ok';

		if( !preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/', $bookingSelectedDate ) ) {
			$statusReport = esc_html__('Selected calendar date is not valid', 'seatreg');
		}

		return $statusReport;
	}

	protected function sameEmailBookingCheck($email, $emailLimit) {
		$statusReport = 'ok';

		$sameEmailBookingsCount = (int)SeatregBookingRepository::getBookingsCountWithSameEmail($this->_registrationCode, $this->_bookerEmail);

		if($sameEmailBookingsCount >= $emailLimit) {
			$statusReport = sprintf(esc_html__('Email %s is already used. You are allowed to make %s booking with the same email', 'seatreg'), $email, $emailLimit);
		}

		return $statusReport;
	}
    
    protected function doSeatsExistInRegistrationLayoutCheck() {
		//check if seats are in rooms and seat numbers are correct.
		$bookingsLenght = count($this->_bookings);
		$layoutLenght = count($this->_registrationLayout);
        $status = 'ok';

		for($i = 0; $i < $bookingsLenght; $i++) {
			$searchStatus = 'room-searching';

			for($j = 0; $j < $layoutLenght; $j++) {
				//looking user selected seat items

				if($this->_registrationLayout[$j]->room->uuid == $this->_bookings[$i]->room_uuid) {
					//found room
					$searchStatus = 'seat-searching';
					
					$boxesLenght = count($this->_registrationLayout[$j]->boxes);

					for($k = 0; $k < $boxesLenght; $k++) {
						//looping boxes
						if($this->_registrationLayout[$j]->boxes[$k]->canRegister === 'true' && $this->_registrationLayout[$j]->boxes[$k]->id == $this->_bookings[$i]->seat_id) {
							
							//found box
							if($this->_registrationLayout[$j]->boxes[$k]->status == 'noStatus') {
								//seat is available
								$searchStatus = 'seat-nr-check';
								$seatPrefix = property_exists($this->_registrationLayout[$j]->boxes[$k], 'prefix') ? $this->_registrationLayout[$j]->boxes[$k]->prefix : '';
							
								if($seatPrefix . $this->_registrationLayout[$j]->boxes[$k]->seat == $this->_bookings[$i]->seat_nr) {
									$searchStatus = 'seat-ok';
								}

							}else {
								$searchStatus = 'seat-taken';
							}

							break;
						}
						
					} //end of boxes loop

					break;
				}
			}//end of room loop

			if($searchStatus == 'room-searching') {
				$status = 'Room '. esc_html($this->_bookings[$i]->room_name) . ' was not found';
				$allCorrect = false;

				break;
			}else if($searchStatus == 'seat-searching') {
				$status = 'id '. esc_html($this->_bookings[$i]->seat_id) . ' was not found';
				$allCorrect = false;

				break;
			}else if($searchStatus == 'seat-nr-check') {
				$status = 'id '. esc_html($this->_bookings[$i]->seat_nr) . ' number was not correct';
				$allCorrect = false;

				break;
			}else if($searchStatus == 'seat-taken') {
				$status = 'id '. esc_html($this->_bookings[$i]->seat_id) . ' is not available';
				$allCorrect = false;
				
				break;
			}

		} //end of data loop

		return $status;
    }

	protected function registrationStartTimeCheck() {
		$statusReport = 'ok';

		if( !SeatregTimeService::registrationStartTimeCheck($this->_registrationStartTime) ) {
			$statusReport = esc_html__('Registration has not yet started today', 'seatreg');
		}

		return $statusReport;
	}

	protected function registrationEndTimeCheck() {
		$statusReport = 'ok';

		if( !SeatregTimeService::registrationEndTimeCheck($this->_registrationEndTime) ) {
			$statusReport = esc_html__('Registration has ended for today', 'seatreg');
		}

		return $statusReport;
	}

	protected function wpUserLimitCheck($userId, $registrationCode) {
		$statusReport = 'ok';
		$bookingsByUser = SeatregBookingRepository::getUserBookings($userId, $registrationCode);

		if( $bookingsByUser >= $this->_wp_user_booking_limit ) {
			$statusReport = sprintf(esc_html__('Allowed number of bookings per user is %s', 'seatreg'), $this->_wp_user_booking_limit);
		}

		return $statusReport;
	}

	protected function wpUserBookingsSeatLimitCheck($userId, $registrationCode, $newBookingsLength) {
		$statusReport = 'ok';
		$bookingsByUser = SeatregBookingRepository::getUserBookings($userId, $registrationCode);

		if( ($bookingsByUser + $newBookingsLength) > $this->_wp_user_bookings_seat_limit ) {
			$statusReport = sprintf(esc_html__('Allowed number of total booked seats per user is %s. You have booked previously %s ', 'seatreg'), $this->_wp_user_bookings_seat_limit, $bookingsByUser);
		}

		return $statusReport;
	}
    
    protected function getRegistrationAndOptions() {
		$result = SeatregRegistrationRepository::getRegistrationWithOptionsByCode($this->_registrationCode);
		
		$this->_registrationStartTimestamp = $result->registration_start_timestamp;
		$this->_registrationEndTimestamp = $result->registration_end_timestamp;
		$this->_registrationLayout = json_decode($result->registration_layout)->roomData;
		$this->_registrationLayoutFull = json_decode($result->registration_layout);
        $this->_registrationName = $result->registration_name;
		$this->_maxSeats = $result->seats_at_once;
		$this->_requireBookingEmailConfirm = $result->booking_email_confirm;
		$this->_createdCustomFields = json_decode($result->custom_fields ?? '[]');
		$this->_emailVerificationSubject = $result->verification_email_subject;
		$this->_emailVerificationTemplate = $result->email_verification_template;
		$this->_pendingBookingSubject = $result->pending_booking_email_subject;
		$this->_pendingBookingTemplate = $result->pending_booking_email_template;
		$this->_approvedBookingSubject = $result->approved_booking_email_subject;
		$this->_approvedBookingTemplate = $result->approved_booking_email_template;
		$this->_sendApprovedBookingEmail = $result->send_approved_booking_email;
		$this->_emailFromAddress = $result->email_from_address;
		$this->_bookingSameEmailLimit = is_null($result->booking_email_limit) ? null : (int)$result->booking_email_limit;
		$this->_usingCalendar = $result->using_calendar === '1';
		$this->_calendarDates = $result->calendar_dates ? explode(',', $result->calendar_dates) : [];
		$this->_registrationStartTime = $result->registration_start_time;
		$this->_registrationEndTime = $result->registration_end_time;
		$this->_require_wp_login = $result->require_wp_login;
		$this->_wp_user_booking_limit = $result->wp_user_booking_limit ? (int)$result->wp_user_booking_limit: null;
		$this->_wp_user_bookings_seat_limit = $result->wp_user_bookings_seat_limit ? (int)$result->wp_user_bookings_seat_limit: null;
		$this->_require_name = $result->require_name === '1';
		
        if($result->gmail_required == '1') {
			$this->_gmailNeeded = true;
        }
        
		if($result->registration_open == '0') {
			$this->_isRegistrationOpen = false;
        }
        
		if($result->use_pending == '0') {
			$this->_insertState = 2;  //now all registrations will be confirmed
        } 

        if($result->registration_password != null) {
			$this->_registrationPassword = $result->registration_password;
        }

		$this->_sendNewBookingNotificationEmail = $result->notify_new_bookings;

		$this->_sendNewPendingBookingNotificationBookerEmail = $result->notify_booker_pending_booking;
	}
}