<?php

//===========
	/* For booking confirm */
//===========

require_once('Booking.php');
require_once('emails.php');
require_once('constants.php');
require_once('./util/registration_time_status.php');

class NewBooking extends Booking {
	public $reply;
	protected $_valid = true;
	protected $_confirmationCode;
	protected $_bookings;
	protected $_registrationLayout;
	protected $_registrationCode;
	protected $_registrationStartTimestamp;
	protected $_registrationEndTimestamp;
	protected $_registrationPassword = null;  //registration password if set. null default
	protected $_isRegistrationOpen = true;
	protected $_bookindId;
	protected $_registrationOwnerEmail;
	protected $_maxSeats = 1;  //how many seats per booking can be booked
	protected $_gmailNeeded = false;  //require gmail address from registrants
	
	public function __construct($code){
		$this->_confirmationCode = $code;
	}

	protected function getNotConfirmedBookings() {
		//find out if confirmation code is in db and return all bookings with that code
		global $wpdb;
		global $seatreg_db_table_names;

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE conf_code = %s
			AND status = 0",
			$this->_confirmationCode
		) );

		$registration = $wpdb->get_row( $wpdb->prepare(
			"SELECT registration_layout FROM $seatreg_db_table_names->table_seatreg
			WHERE registration_code = %s",
			$rows[0]->registration_code
		) );

		if(count($rows) == 0) {
			$this->reply = __('This booking is already confirmed/expired/deleted', 'seaterg');
			$this->_valid = false;
		}else {
			$roomData = json_decode($registration->registration_layout)->roomData;
			$this->_bookings = $rows; 
			foreach ($this->_bookings as $booking) {
				$booking->room_name = seatreg_get_room_name_from_layout($roomData, $booking->room_uuid);
			}
			$this->_registrationCode = $this->_bookings[0]->registration_code; 
			$this->_bookingId = $this->_bookings[0]->booking_id;
		}
	}

	public function confirmBookings() {
		global $wpdb;
		global $seatreg_db_table_names;

		$wpdb->update( 
			$seatreg_db_table_names->table_seatreg_bookings,
			array( 
				'status' => $this->_insertState
			), 
			array('booking_id' => $this->_bookingId), 
			'%d',
			'%s'
		);

		if($this->_insertState == 1) {
			_e('You booking is now in pending state. Registration owner must approve it', 'seatreg');
			echo '.<br><br>';
		}else {
			_e('You booking is now confirmed', 'seatreg');
			echo '<br><br>';
		}
		$bookingCheckURL = SEATREG_PLUGIN_FOLDER_URL . 'php/booking_check.php?registration=' . $this->_registrationCode . '&id=' . $this->_bookingId;
		printf(
			__('You can look your booking at %s', 'seatreg'), 
			"<a href='$bookingCheckURL'>$bookingCheckURL</a>"
		);

		if($this->_sendNewBookingNotificationEmail) {
			$seatsString = $this->generateSeatString();
			seatreg_send_booking_notification_email($this->_registrationName, $seatsString, $this->_sendNewBookingNotificationEmail);
		}
	}

	public function startConfirm() {
		$this->getNotConfirmedBookings();

		//1 step. Does confirmation code exists? Is booking already confirmed?
		if($this->_valid == false) {
			echo $this->reply;
			return;
		}

		//2 step. Get registration with options
		$this->getRegistrationAndOptions();

		//3 step. Is registtration open?
		if(!$this->_isRegistrationOpen) {
			_e('Registration is closed at the moment', 'seaterg');
			return;
		}
		$registrationTimeCheck = seatreg_registration_time_status($this->_registrationStartTimestamp, $this->_registrationEndTimestamp);
		if($registrationTimeCheck != 'run') {
			_e('Registration is not open', 'seatreg');
			return;
		}

		//4 step. Check if all selected seats are ok
		$seatsStatusCheck = $this->doSeatsExistInRegistrationLayoutCheck();
		if($seatsStatusCheck != 'ok') {
			echo $seatsStatusCheck;
			return;
		}

		//5 step. Check if seat/seats is already bron or taken
		$seatsOpenCheck = $this->isAllSelectedSeatsOpen(); 
		if($seatsOpenCheck != 'ok') {
			echo $seatsOpenCheck;
			exit();
		}	

		//6 step. confirm bookings
		$this->confirmBookings();  //this also updates structure
	}
}