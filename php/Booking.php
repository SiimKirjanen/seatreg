<?php

class Booking {
	protected $_requireBookingEmailConfirm = true;
	protected $_insertState = 1;  //all bookings will have status = 1 (pending). if 2 then (confirmed)
	protected $_registrationName;

    protected function generateSeatString() {
    	$dataLen = count($this->_bookings);
    	$seatsString = '';

    	for($i = 0; $i < $dataLen; $i++) {
    		$seatsString .= 'Seat nr: ' . $this->_bookings[$i]->seat_nr . ' from room: ' . $this->_bookings[$i]->room_name . '<br/>'; 
		}
		
    	return $seatsString;
    }

    protected function isAllSelectedSeatsOpen() {  
		global $wpdb;
		global $seatreg_db_table_names;

		$bookingsLength = count($this->_bookings);
		$bookedBookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE seatreg_code = %s AND status != 0",
			$this->_registrationCode
		) );
		$bookedBookingsLength = count($bookedBookings);
		$statusReport = 'ok';

		for($i = 0; $i < $bookingsLength; $i++) {
			for($j = 0; $j < $bookedBookingsLength; $j++) {
				if($this->_bookings[$i]->seat_id == $bookedBookings[$j]->seat_id) {
					$statusReport = 'Someone has taken seat '. $this->_bookings[$i]->seat_nr . ' in room ' . $this->_bookings[$i]->room_name . ' before you. Please refresh registration page and choose another seat.';

					break 2;
				}
			}
		}

		return $statusReport;
	}

    protected function registrationTimeStatus($startUnix, $endUnix) {
		$unix = round(microtime(true) * 1000);

		if($startUnix == null && $endUnix == null) {
			return 'run';
		}

		if($endUnix == null && $unix > $startUnix) {
			return 'run';
		}

		if($endUnix == null && $unix < $startUnix) {
			return 'wait';
		}

		if($startUnix == null && $unix < $endUnix) {
			return 'run';
		}

		if($startUnix == null && $unix > $endUnix) {
			return 'end';
		}

		if($startUnix < $unix && $endUnix > $unix) {
			return 'run';
		}

		if($unix > $endUnix) {
			return 'end';
		}

		if($startUnix > $unix) {
			return 'wait';
		}
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
				$status = 'Room '. $this->_bookings[$i]->room_name . ' was not found';
				$allCorrect = false;
				break;
			}else if($searchStatus == 'seat-searching') {
				$status = 'id '. $this->_bookings[$i]->seat_id . ' was not found';
				$allCorrect = false;
				break;
			}else if($searchStatus == 'seat-nr-check') {
				$status = 'id '. $this->_bookings[$i]->seat_nr . ' number was not correct';
				$allCorrect = false;
				break;
			}else if($searchStatus == 'seat-taken') {
				$status = 'id '. $this->_bookings[$i]->seat_id . ' is not available';
				$allCorrect = false;
				break;
			}

		} //end of data loop

		return $status;
    }
    
    protected function getRegistrationAndOptions() {
		global $wpdb;
		global $seatreg_db_table_names;

		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT 
			a.registration_name, 
			a.registration_layout, 
            b.seats_at_once,
            b.gmail_required,
			b.registration_start_timestamp, 
			b.registration_end_timestamp, 
			b.registration_open, 
			b.use_pending, 
			b.notify_new_bookings,
			b.booking_email_confirm 
			FROM $seatreg_db_table_names->table_seatreg AS a 
			INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b 
			ON a.registration_code = b.seatreg_code WHERE a.registration_code = %s",
			$this->_registrationCode
		) );

		$this->_registrationStartTimestamp = $result->registration_start_timestamp;
		$this->_registrationEndTimestamp = $result->registration_end_timestamp;
		$this->_registrationLayout = json_decode($result->registration_layout);
        $this->_registrationName = $result->registration_name;
		$this->_maxSeats = $result->seats_at_once;
		$this->_requireBookingEmailConfirm = $result->booking_email_confirm;
        
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
        
		if($result->notify_new_bookings == '1') {
			$this->_sendNewBookingNotification = true;
			/* TODO need to get email where to send notification about new booking */
			/* $stmt = $db->prepare('SELECT email FROM users WHERE id = :id');
			$stmt->execute(array(':id'=>$row['users_id']));
			$row2 = $stmt->fetch(PDO::FETCH_ASSOC);
			$this->_ownerEmail = $row2['email']; */
		}
	}
}