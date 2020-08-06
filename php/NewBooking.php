<?php

//===========
/*for confirm seat selection*/
//===========

class NewBooking {
	protected $_valid = true;
	protected $_confirmationCode;
	protected $_bookings;
    protected $_registrationName;
	protected $_registrationLayout;
	protected $_registrationCode;
	protected $_registrationStartTimestamp;
	protected $_registrationEndTimestamp;
	protected $_isRegistrationOpen = true;
	protected $_insertState = 1;  //all bookings will have status = 1 (pending). if 2 then (confirmed)
	protected $_bookindId;
	protected $_sendNotify = false; //send notification to registration owner that someone has booked a seat
	protected $_ownerEmail; //registration owner email address
	public $reply;

	public function __construct($code){
		$this->_confirmationCode = $code;
	}

	private function generateSeatString() {
    	$dataLen = count($this->_bookings);
    	$seatsString = '';

    	for($i = 0; $i < $dataLen; $i++) {
    		$seatsString .= 'Seat nr: ' . $this->_bookings[$i]->seat_nr . ' from room: ' . $this->_bookings[$i]->room_name . '<br/>'; 
		}
		
    	return $seatsString;
    }

	protected function getBookings() {
		//find out if confirmation code is in db and return all bookings with that code
		global $wpdb;
		global $seatreg_db_table_names;

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE conf_code = %s",
			$this->_confirmationCode
		) );

		if(count($rows) == 0) {
			$this->reply = 'Nothing to confirm.<br>This request is confirmed/expired/deleted.<br>';
			$this->_valid = false;
		}else {
			$this->_bookings = $rows; 
			$this->_registrationCode = $this->_bookings[0]->seatreg_code; 
			$this->_bookingId = $this->_bookings[0]->booking_id;
		}
	}

	protected function getRegistrationAndOptions() {
		global $wpdb;
		global $seatreg_db_table_names;

		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT 
			a.registration_name, 
			a.registration_layout, 
			b.registration_start_timestamp, 
			b.registration_end_timestamp, 
			b.registration_open, 
			b.use_pending, 
			b.notify_new_bookings 
			FROM $seatreg_db_table_names->table_seatreg AS a 
			INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b 
			ON a.registration_code = b.seatreg_code WHERE a.registration_code = %s",
			$this->_registrationCode
		) );

		$this->_registrationStartTimestamp = $row->registration_start_timestamp;
		$this->_registrationEndTimestamp = $row->registration_end_timestamp;
		$this->_registrationLayout = json_decode($row->registration_layout);
		$this->_registrationName = $row->registration_name;

		if($row->registration_open == '0') {
			$this->_isRegistrationOpen = false;
		}
		if($row->use_pending == '0') {
			$this->_insertState = 2;  //now all registrations will be confirmed
		} 

		if($row->notify_new_bookings == '1') {
			$this->_sendNotify = true;
			/* TODO */
			/* $stmt = $db->prepare('SELECT email FROM users WHERE id = :id');
			$stmt->execute(array(':id'=>$row['users_id']));
			$row2 = $stmt->fetch(PDO::FETCH_ASSOC);
			$this->_ownerEmail = $row2['email']; */
		}
	}

	protected function doSeatsExistInRegistrationLayout() {
		//check if seats are in rooms and seat numbers are correct.
		$bookingsLenght = count($this->_bookings);
		$layoutLenght = count($this->_registrationLayout);
		$allCorrect = true;

		for($i = 0; $i < $bookingsLenght; $i++) {
			$searchStatus = 'room-searching';

			for($j = 0; $j < $layoutLenght; $j++) {
				//looking user selected seat items

				if($this->_registrationLayout[$j]->room[1] == $this->_bookings[$i]->room_name) {
					//found room
					$searchStatus = 'seat-searching';
					
					$boxesLenght = count($this->_registrationLayout[$j]->boxes);

					for($k = 0; $k < $boxesLenght; $k++) {
						//looping boxes
						if($this->_registrationLayout[$j]->boxes[$k][8] == 'true' && $this->_registrationLayout[$j]->boxes[$k][7] == $this->_bookings[$i]->seat_id) {
							
							//found box
							if($this->_registrationLayout[$j]->boxes[$k][10] == 'noStatus') {
								//seat is available
								$searchStatus = 'seat-nr-check';
							
								if($this->_registrationLayout[$j]->boxes[$k][9] == $this->_bookings[$i]->seat_nr) {
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
				$this->reply = 'Room '. $this->_bookings[$i]->room_name . ' was not found';
				$allCorrect = false;
				break;
			}else if($searchStatus == 'seat-searching') {
				$this->reply = 'id '. $this->_bookings[$i]->seat_id . ' was not found';
				$allCorrect = false;
				break;
			}else if($searchStatus == 'seat-nr-check') {
				$this->reply = 'id '. $this->_bookings[$i]->seat_nr . ' number was not correct';
				$allCorrect = false;
				break;
			}else if($searchStatus == 'seat-taken') {
				$this->reply = 'id '. $this->_bookings[$i]->seat_id . ' is not available';
				$allCorrect = false;
				break;
			}

		} //end of data loop

		if(!$allCorrect) {
			$this->_valid = false;
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
			echo 'Thank you. <br>';
			echo 'You booking is now in pending state. Registration owner must confirm it.<br><br>';
		}else {
			echo 'Thank you. <br>';
			echo 'You booking is now confirmed.<br><br>';
		}
		$seatsString = $this->generateSeatString();
		echo $seatsString;

		if($this->_sendNotify) {
			/* $mail2->Subject = "$this->_registrationName has a new booking";
			$mail2->Body = "Hello <br>This is a notification email telling you that $this->_registrationName has a new booking <br><br> $seatsString <br><br> You can disable booking notification in options if you don't want to receive them. ";
			$mail2->AltBody = "Hello \r\n  this is a notification email telling you that $this->_registrationName has a new booking \r\n \r\n $seatsString \r\n \r\n You can disable booking notification in options if you don't want to receive them.";
			$mail2->send(); */
		}
	}

	private function isAllSelectedSeatsOpen() {  
		global $wpdb;
		global $seatreg_db_table_names;

		$bookingsLength = count($this->_bookings);
		$bookedBookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE seatreg_code = %s AND status != 0",
			$this->_registrationCode
		) );
		$bookedBookingsLength = count($bookedBookings);

		for($i = 0; $i < $bookingsLength; $i++) {
			for($j = 0; $j < $bookedBookingsLength; $j++) {
				if($this->_bookings[$i]->seat_id == $bookedBookings[$j]->seat_id) {
					echo 'Someone has taken seat ',$this->_bookings[$i]->seat_nr, ' in room ', $this->_bookings[$i]->room_name, ' before you. Please refresh registration page and choose another seat.';

					return false;
				}
			}
		}

		return true;
	}

	public function startConfirm() {
		$this->getBookings();

		//1 step. Does confirmation code exist?
		if($this->_valid == false) {
			echo $this->reply;
			return;
		}

		//2 step. Get registration with options
		$this->getRegistrationAndOptions();

		//3 step. Is registtration open?
		if(!$this->_isRegistrationOpen) {
			echo 'Registration is closed at the moment';
			return;
		}

		//4 step. Check if all selected seats are ok
		$this->doSeatsExistInRegistrationLayout();

		if($this->_valid == false) {
			echo $this->reply;
			return;
		}

		//5 step. Check if seat/seats is already bron or taken
		if(!$this->isAllSelectedSeatsOpen()) {
			exit();
		}

		//6 step. time check
		$registrationTime = registrationTimeStatus($this->_registrationStartTimestamp, $this->_registrationEndTimestamp);

		if($registrationTime != 'run') {
			echo 'Registration is not open (time)';
			return;
		}

		//7 step. confirm bookings
		$this->confirmBookings();  //this also updates structure

		echo '<br/>Thank you';
	}
}