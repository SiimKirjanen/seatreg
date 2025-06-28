<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

//===========
	/* For booking confirm */
//===========

class SeatregConfirmBooking extends SeatregBooking {
	public $reply;
	protected $_confirmationCode;
	protected $_bookindId;
	protected $_registrationOwnerEmail;
	protected $_bookerEmail; //confirm email is send to this address
	protected $_selectedBookingCalendarDate; //in calendar mode user selected calendar date
	private $_bookingId;
	protected $_wpUserId = null;
	
	public function __construct($code){
		$this->_confirmationCode = $code;
	}

	protected function init() {
		//find out if confirmation code is in db and return all bookings with that code
		$rows = SeatregBookingRepository::getBookingByConfCode($this->_confirmationCode);

		if( !$rows ) {
			$this->reply = esc_html__('This booking is already confirmed/expired/deleted', 'seatreg');
			$this->_valid = false;
		}else {
			$registration = SeatregRegistrationRepository::getRegistrationByCode($rows[0]->registration_code);
			$roomData = json_decode($registration->registration_layout)->roomData;
			$this->_bookings = $rows; 
			foreach ($this->_bookings as $booking) {
				$booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($roomData, $booking->room_uuid);
			}
			$this->_registrationCode = $this->_bookings[0]->registration_code; 
			$this->_bookingId = $this->_bookings[0]->booking_id;
			$this->_bookerEmail = $this->_bookings[0]->booker_email;
			$this->_seatPasswords = json_decode(stripslashes_deep($this->_bookings[0]->seat_passwords));
			$this->_selectedBookingCalendarDate = $this->_bookings[0]->calendar_date;
			$this->_wpUserId = $this->_bookings[0]->logged_in_user_id;
		}
	}

	public function confirmBookings() {
		global $wpdb;
		global $seatreg_db_table_names;

		$approvedTimestamp = ($this->_insertState == 2) ? time() : null;
		$rowsUpdated = $wpdb->update( 
			$seatreg_db_table_names->table_seatreg_bookings,
			array( 
				'status' => $this->_insertState,
				'booking_confirm_date' => $approvedTimestamp 
			), 
			array('booking_id' => $this->_bookingId), 
			array('%d', '%d', '%s')
		);

		if(!$rowsUpdated) {
			esc_html_e('Something went wrong while confirming your booking', 'seatreg');
			die();
		}
		$bookingCheckURL = seatreg_get_registration_status_url($this->_registrationCode, $this->_bookingId);

		SeatregActionsService::triggerBookingSubmittedAction($this->_bookingId);

		if($this->_insertState == 1) {
			seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to pending state by the system (Booking confirm link)', false);
			SeatregActionsService::triggerBookingPendingAction($this->_bookingId);

			if ($this->_sendNewPendingBookingNotificationBookerEmail) {
				seatreg_send_pending_booking_email($this->_registrationName, $this->_bookerEmail, $bookingCheckURL, $this->_pendingBookingTemplate, $this->_emailFromAddress, $this->_pendingBookingSubject);
			}
			esc_html_e('You booking is now in pending state. Registration owner must approve it', 'seatreg');
			echo '.<br><br>';
		}else {
			seatreg_add_activity_log('booking', $this->_bookingId, 'Booking set to approved state by the system (Booking confirm link)', false);
			SeatregActionsService::triggerBookingApprovedAction($this->_bookingId);
			seatreg_send_approved_booking_email($this->_bookingId, $this->_registrationCode, $this->_approvedBookingTemplate);
			esc_html_e('You booking is now confirmed', 'seatreg');
			echo '<br><br>';
		}

		printf(
			esc_html__('You can see your booking status at the following link %s', 'seatreg'), 
			"<a href='" . esc_url($bookingCheckURL) . "'>" . esc_html($bookingCheckURL) . "</a>"
		);

		if($this->_sendNewBookingNotificationEmail) {
			seatreg_send_booking_notification_email($this->_registrationCode, $this->_bookingId, $this->_sendNewBookingNotificationEmail);
		}
	}

	public function startConfirm() {
		$this->init();

		if(!$this->_valid) {
			esc_html_e($this->reply);

			return;
		}

		$this->getRegistrationAndOptions();

		//1 step
		//Selected seat limit check
		if(!$this->seatsLimitCheck()) {
			esc_html_e('Error. Seat limit exceeded', 'seatreg');

			return;
		}

		//2 step. Does confirmation code exists? Is booking already confirmed?
		if(!$this->_valid) {
			esc_html_e($this->reply);

			return;
		}
		
		//3 step. Is registtration open?
		if(!$this->_isRegistrationOpen) {
			esc_html_e('Registration is closed at the moment', 'seatreg');

			return;
		}
		$registrationTimeCheck = seatreg_registration_time_status($this->_registrationStartTimestamp, $this->_registrationEndTimestamp);
		if($registrationTimeCheck != 'run') {
			esc_html_e('Registration is not open', 'seatreg');

			return;
		}

		//4 step. Check if all selected seats are ok
		$seatsStatusCheck = $this->doSeatsExistInRegistrationLayoutCheck();
		if($seatsStatusCheck != 'ok') {
			echo esc_html($seatsStatusCheck);

			return;
		}

		//5 step. Check if seat/seats is already bron or taken
		$seatsOpenCheck = $this->isAllSelectedSeatsOpen($this->_selectedBookingCalendarDate); 
		if($seatsOpenCheck != 'ok') {
			echo esc_html($seatsOpenCheck);

			exit();
		}
		
		//6 step. Seat/seats lock check
		$lockStatus = $this->seatLockCheck();
		if($lockStatus != 'ok') {
			echo esc_html($lockStatus);

			return;
		}

		//7 step. Seat/seats password check
		$passwordStatus = $this->seatPasswordCheck();
		if($passwordStatus != 'ok') {
			echo esc_html($passwordStatus);

			return;
		}

		//8 step. If enebled check booking email limit
		if($this->_bookingSameEmailLimit) {
			$sameEmailBookingCheckStatus = $this->sameEmailBookingCheck($this->_bookerEmail, $this->_bookingSameEmailLimit);

			if($sameEmailBookingCheckStatus != 'ok') {
				echo esc_html($sameEmailBookingCheckStatus);
	
				return;
			}
		}

		//9 step. Custom field validation
		$bookingCustomFields = [];

		foreach($this->_bookings as $booking) {
			$customFieldDecoded = json_decode($booking->custom_field_data);
			array_push($bookingCustomFields, $customFieldDecoded);
		}

		$bookingCustomFieldsEncoded = json_encode($bookingCustomFields);
		$customFieldValidation = SeatregDataValidation::validateBookingCustomFields($bookingCustomFieldsEncoded, $this->_maxSeats, $this->_createdCustomFields, $this->_registrationCode);
		if( !$customFieldValidation->valid ) {
			echo esc_html($customFieldValidation->errorMessage);

			return false;
		}

		//10 step. Calendar mode validation
		if( $this->_usingCalendar ) {
			$calendarDateFormatCheck = $this->calendarDateFormatCheck( $this->_selectedBookingCalendarDate );
			if($calendarDateFormatCheck != 'ok') {
				echo esc_html($calendarDateFormatCheck);

				return;
			}

			$calendarDateCheck = $this->calendarDateValidation( $this->_selectedBookingCalendarDate );
			if($calendarDateCheck != 'ok') {
				echo esc_html($calendarDateCheck);

				return;
			}

			$calendarDatePastCheck = $this->calendarDatePastDateCheck( $this->_selectedBookingCalendarDate );
			if($calendarDatePastCheck != 'ok') {
				echo esc_html($calendarDatePastCheck);

				return;
			}
		}

		//11. start time check
		$startTimeCheck = $this->registrationStartTimeCheck();
		if($startTimeCheck !== 'ok') {
			echo esc_html($startTimeCheck);

			return;
		}

		//12. end time check
		$endTimeCheck = $this->registrationEndTimeCheck();
		if($endTimeCheck !== 'ok') {
			echo esc_html($endTimeCheck);

			return;
		}

		//13. WP user booking limit restriction.
		if( $this->_wp_user_booking_limit !== null && $this->_wpUserId ) {
			$wpUserLimitStatus = $this->wpUserLimitCheck( $this->_wpUserId, $this->_registrationCode );

			if( $wpUserLimitStatus !== 'ok' ) {
				echo esc_html($wpUserLimitStatus);

				return;
			}
		}

		//14. WP user bookings seats limit restriction.
		if( $this->_wp_user_bookings_seat_limit !== null && $this->_wpUserId ) {
			$wpUserBookingsSeatsLimitStatus = $this->wpUserBookingsSeatLimitCheck( $this->_wpUserId, $this->_registrationCode, count($this->bookings) );

			if( $wpUserBookingsSeatsLimitStatus !== 'ok' ) {
				echo esc_html($wpUserBookingsSeatsLimitStatus);

				return;
			}
		}
	
		$this->confirmBookings();
	}
}