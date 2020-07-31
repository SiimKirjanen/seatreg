<?php

//===========
	/*data coming from registration view. Someone wants to book a seat*/
//===========

require_once('/libs/PHPMailer/PHPMailerAutoload.php');

class ViewData {
	public $response; //response object
	protected $_isValid = true;
	protected $_email; //confirm email is send to this address
	protected $_bookings; //seat bookings 
	protected $_viewCode; //url code
	protected $_struct;  //registration layout
	protected $_registriId;
	protected $_seatLoc;
	protected $_gmailNeeded = false;  //require gmail address from registrants
	protected $_startUnix; //when registration stars
	protected $_endUnix; //when registration ends
	protected $_subPassword;  //user submitted password
	protected $_regPassword = null;  //registration password if set. null default
	protected $_isOpen = true; //is registration open
	protected $_bookingId; //id for booking
	protected $_maxSeats = 1;  //how many seats per booking
	
    public function __construct($firstname, $lastname, $email, $seatID, $seatNr, $seatRoom, $emailToSend, $code, $pw, $customFields, $resp){
    	//$_POST['FirstName'], $_POST['LastName'], $_POST['Email'], $_POST['em'], $_POST['c'], $_POST['pw'], $_POST['custom'], $resp
    	
    	$bookings = [];
    	$customFieldData = json_decode( stripslashes_deep($customFields) );

    	foreach ($firstname as $key => $value) {
    		$booking = new stdClass();
    		$booking->firstname = $value;
    		$booking->lastname = $lastname[$key];
    		$booking->email = $email[$key];
    		$booking->seatId = $seatID[$key];
    		$booking->seatNr = $seatNr[$key];
    		$booking->seatRoom = $seatRoom[$key];
    		$booking->customField = $customFieldData[$key];
    		$bookings[] = $booking;
    	}

        $this->_bookings = $bookings;
        $this->_viewCode = $code;
        $this->response = $resp;
        $this->_email = $emailToSend;
        $this->_subPassword = $pw;
        //$this->seatLoc = new stdClass();
      	$this->setUp();
	}
	
    private function generateSeatString() {
    	$dataLen = count($this->_data);
    	$seatsString = '';

    	for($i = 0; $i < $dataLen; $i++) {
    		$seatsString .= 'Seat nr: ' . $this->_data[$i][2] . ' from room: ' . $this->_data[$i][1] . '<br/>'; 
		}
		
    	return $seatsString;
    }

	public function validateData() {


		//Validation
	
		/*if( !$this->seatreg_validate_data() ) {
			//print_r($this->data);

			$this->response->setError( __('Validation error','seatreg') );
			//
			//
			//$this->response->setError( json_encode($this->_data) );
			return;
		}*/

		//1.step
		$this->isSeperateSeats();

		if(!$this->_isValid) {
			$this->response->setError('Error. Dublicated seats');
			return;
		}

		//password check if needed

		if($this->_regPassword != null) {
			if($this->_regPassword != $this->_subPassword) {
				//registration password and user submitted passwords are not the same
				$this->response->setError('Error. Password mismatch!');
				return;
			}
		}

		//2.step
		//seat room, id, nr and is availvable check.
		$this->doseSeatsExistInRooms();

		if(!$this->_isValid) {
			return;
		}

		//3.step. Email check if needed
		if($this->_gmailNeeded) {
			$gmailReg = '/^[a-z0-9](\.?[a-z0-9]){2,}@g(oogle)?mail\.com$/';

			if(!preg_match($gmailReg, $this->_email)) {
				$this->response->setError('Gmail needed!');
				return;
			}
		}

		//4.step. Time check. is registration open.
		if ($this->_isOpen == false) {
			$this->response->setError('Registration is closed');
			return;
		}

		$registrationTime = $this->registrationTimeStatus($this->_startUnix, $this->_endUnix);
		if($registrationTime != 'run') {
			$this->response->setError('Registration is not open (time)');
			return;
		}

		//5.step. Check if seat/seats are allready taken
		$bookStatus = $this->isAllSeatsOpen(); 

		if($bookStatus != 'ok') {
			$this->response->setError($bookStatus);
			return;
		}

		$this->insertPreRegistration();
	}

	public function seatreg_validate_data() {

		//[["b12","Esik","12","siim","k","kkk",[{"label":"klass","value":"2"},{"label":"suitsetaja","value":false},{"label":"nickname","value":"greg"}]]]

		/*$packReg = '/^\[(\[[b0-9"]{1,22},[0-9a-zA-ZÜÕÖÄüõöä\s"]{1,22},[0-9"]{1,5},[0-9a-zA-ZÜÕÖÄüõöä\s"]{1,102},[0-9a-zA-ZÜÕÖÄüõöä\s"]{1,102},[a-zA-ZÜÕÖÄüõöä\s-@."]{1,52},\[({"label":[0-9a-zA-ZÜÕÖÄüõöä\s@."]{1,102},"value":[0-9a-zA-ZÜÕÖÄüõöä\s@."-]{1,52}},?){0,6}\]\],?){1,'.$this->_maxSeats.'}\]$/';
*/

		$packReg = '/^\[(\[[b0-9"]{1,22},[0-9a-zA-ZÜÕÖÄüõöä\s"]{1,22},[0-9"]{1,5},[0-9a-zA-ZÜÕÖÄüõöä\s"]{1,102},[0-9a-zA-ZÜÕÖÄüõöä\s"]{1,102},[a-zA-ZÜÕÖÄüõöä\s-@."]{1,52},\[({"label":[0-9a-zA-ZÜÕÖÄüõöä\s@."]{1,102},"value":[0-9a-zA-ZÜÕÖÄüõöä\s@."-]{1,52}},?){0,6}\]\],?){1,'.$this->_maxSeats.'}\]$/';

		if( !preg_match($packReg, $this->_dataPack) ) {
			return false;
		}

		return true;
	}

	public function getStatus() {
		return $this->_isValid;
	}

	private function emailCheck() {
	}

	private function isSeperateSeats() {
		//check so each seat is different. Prevents dublicate booking on same seat
		$seatIds = array();
		$dataLen = count($this->_bookings);

		for($i = 0; $i < $dataLen; $i++) {
			//echo 'checking ',$this->_data[$i][0], '-----';
			if(!in_array($this->_bookings[$i]->seatId, $seatIds)) {
				//print_r($seatIds);
				//echo $this->_data[$i][0], ' not in array. insert it--------';
				array_push($seatIds, $this->_bookings[$i]->seatId);
			}else {
				//echo $this->_data[$i][0], ' is already in. return false--------';
				$this->_isValid = false;
				break;
			}
		}
	}

	private function doseSeatsExistInRooms() {
		//check if seats are in rooms and seat numbers are correct. returns true if all is ok. false if wrong

		$dataLen = count($this->_bookings);
		$structLen = count($this->_struct);
		$allCorrect = true;

		for($i = 0; $i < $dataLen; $i++) {
			$searchStatus = 'room-searching';

			for($j = 0; $j < $structLen; $j++) {
				//looking user selected seat items

				if($this->_struct[$j]->room[1] == $this->_bookings[$i]->seatRoom) {
					//found room
					$searchStatus = 'seat-searching';
					$boxLen = count($this->_struct[$j]->boxes);

					for($k = 0; $k < $boxLen; $k++) {
						//looping boxes
						if($this->_struct[$j]->boxes[$k][8] == 'true' && $this->_struct[$j]->boxes[$k][7] == $this->_bookings[$i]->seatId) {
							//found box

							if($this->_struct[$j]->boxes[$k][10] == 'noStatus') {
								//seat is available
								$searchStatus = 'seat-nr-check';
							
								if($this->_struct[$j]->boxes[$k][9] == $this->_bookings[$i]->seatNr) {
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
				//did not find this room
				//$this->response->setError('<h1>Room '. $this->_data[$i][1] . ' was not found</h1>');
				$this->response->setError('Room ' . $this->_bookings[$i]->seatRoom . ' dose not exist anymore or has been renamed. Please refresh page and try again');
				$allCorrect = false;
				break;

			}else if($searchStatus == 'seat-searching') {
				//seat dose not exist
				//$this->response->setError('id '. $this->_data[$i][0] . ' was not found');
				$this->response->setError('Your selected seat/seats have been deleted or updated. Please refresh page and try again');
				$allCorrect = false;
				break;
			}else if($searchStatus == 'seat-nr-check') {
				//$this->response->setError('id '. $this->_data[$i][0] . ' number was not correct');
				$this->response->setError('Your selected seat/seats have been deleted or updated. Please refresh page and try again');
				$allCorrect = false;
				break;
			}else if($searchStatus == 'seat-taken') {
				//$this->response->setError('id '. $this->_data[$i][0] . ' is not available');
				$this->response->setError('Someone has managed to register seat ' . $this->_bookings[$i]->seatNr . ' in room ' . $this->_bookings[$i]->seatRoom . ' before you. Please refresh page and try again');
				$allCorrect = false;
				break;
			}

		} //end of data loop

		if(!$allCorrect) {
			$this->_isValid = false;
		}
	}

	private function isAllSeatsOpen() {  //.
		global $wpdb;
		global $seatreg_db_table_names;

		$dataLength = count($this->_bookings);
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT seat_id FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE seatreg_code = %s",
			$this->_registriId
		) );

		/*$stmt = $db -> query("SELECT seat_id FROM registrations WHERE registry_id = $this->_registriId");
		$rows = $stmt->fetchAll(PDO::FETCH_NUM);*/

		$rowsLength = count($rows);
		$statusReport = 'ok';

		//echo '<pre>', print_r($rows), '</pre>';
		for($i = 0; $i < $dataLength; $i++) {
			for($j = 0; $j < $rowsLength; $j++) {
				if(in_array($this->_bookings[$i]->seatId, $rows[$j])) {
					$statusReport = 'Someone has taken seat '. $this->_bookings[$i]->seatNr . ' in room ' . $this->_bookings[$i]->seatRoom . ' before you. Please refresh registration page and choose another seat.';
					break 2;
				}
			}	
		}

		return $statusReport;
	}

	public function insertPreRegistration() {
		//insert registration. user must confirm via email
		//send notification email to registration owen if this option is enabled.
		if($this->_isValid) {
			//insert registration data

			global $wpdb;
			global $seatreg_db_table_names;

			/*$stmt = $db -> prepare('SELECT id FROM registry WHERE code = :code');
			$stmt->execute(array(':code'=>$this->_viewCode));
			$row = $stmt->fetch(PDO::FETCH_ASSOC); 
			$regId = $row['id'];*/

			$dataLength = count($this->_bookings);
			$inserted = true;

			$confCode = sha1(mt_rand(10000,99999).time().$this->_email);
			$this->_bookingId = sha1(mt_rand(10000,99999).time().$this->_email);

			//$this->response->setData( $this->_data[0][0] );
				 
			for($i = 0; $i < $dataLength; $i++) {
				$wpdb->insert( 
					$seatreg_db_table_names->table_seatreg_bookings, 
					array(
						'seatreg_code' => $this->_viewCode, 
						'first_name' => $this->_bookings[$i]->firstname, 
						'last_name' => $this->_bookings[$i]->lastname,
						'email' => $this->_bookings[$i]->email,
						'seat_id' => $this->_bookings[$i]->seatId,
						'seat_nr' => $this->_bookings[$i]->seatNr,
						'room_name' => $this->_bookings[$i]->seatRoom,
						'conf_code' => $confCode, 
						'custom_field_data' => json_encode($this->_bookings[$i]->customField, JSON_UNESCAPED_UNICODE),
						'booking_id' => $this->_bookingId
					), 
					'%s'	
				);

			}
	
			if($inserted) {

				$this->response->setText('mail');

				/*$this->changeCaptcha(3);

				$seatsString = $this->generateSeatString();

				$mail = new PHPMailer; //send confirm mail to client

				//$mail->SMTPDebug = 3;                               // Enable verbose debug output


				/*
				$mail->isSMTP();                                      // Set mailer to use SMTP
				$mail->Host = 'mail.veebimajutus.ee';  // Specify main and backup SMTP servers
				$mail->SMTPAuth = true;                               // Enable SMTP authentication
				$mail->Username = 'confirm@seatreg.com';                 // SMTP username
				$mail->Password = 'mnK2tXMC';                           // SMTP password
				$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
				$mail->Port = 465;                                    // TCP port to connect to
				*/

				/*
				$mail->From = 'confirm@seatreg.com';
				$mail->FromName = 'SeatReg.com';
				//$mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
				$mail->addAddress($this->_email);               // Name is optional
				//$mail->addReplyTo('info@example.com', 'Information');
				//$mail->addCC('cc@example.com');
				//$mail->addBCC('bcc@example.com');

				$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
				//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
				//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
				$mail->isHTML(true);                                  // Set email format to HTML

				$mail->Subject = 'Booking confirmation';


				$mail->Body  = 'Your selected seats are: <br/><br/>' . $seatsString . '
							<p>Click on the link below to confirm your booking</p>
							<a href="https://www.seatreg.com/email_conf.php?r='. $confCode.'" >https://www.seatreg.com/email_conf.php?r='. $confCode .'</a><br/>
							(If you cant click then copy and paste it into your web browser)<br/><br/>After confirmation you can look your booking at<br> <a href="https://www.seatreg.com/booking_check.php?c='. $this->_viewCode.'&i='.$this->_bookingId.'" >https://www.seatreg.com/booking_check.php?c='. $this->_viewCode .'&i='.$this->_bookingId.'</a>';

				$mail->AltBody = "Hi\r\nYour selected seats are:\r\n" . str_replace('<br/>', "\r\n", $seatsString) ."To confirm your email change please copy and paste the following link into your web browser\r\n https://www.seatreg.com/email_conf.php?r=$confCode\r\n";*/

				



				/*if(!$mail->send()) {
					$this->response->setError('We have encountered error ER-3 while performing your request');
					
				} else {

					$this->response->setText('mail');
					
				}*/


			}	
		}
	}

	private function setUp() {
		global $wpdb;
		global $seatreg_db_table_names;

		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT a.*, b.* 
			FROM $seatreg_db_table_names->table_seatreg AS a
			INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
			ON a.registration_code = b.seatreg_code
			WHERE a.registration_code = %s",
			$this->_viewCode
		) );

		$this->_struct = json_decode($result->registration_layout);
		$this->_registriId = $result->id;
		
		if($result->gmail_required == 1) {
		
			$this->_gmailNeeded = true;
		}

		$this->_startUnix = $result->registration_start_timestamp;
		$this->_endUnix = $result->registration_end_timestamp;

		if($result->registration_open == '0') {
			$this->_isOpen = false;
		}

		if($result->registration_password != null) {
			$this->_regPassword = $result->registration_password;
		}

		$this->_maxSeats = $result->seats_at_once;
	}

	private function changeCaptcha($length) {
		$chars = "abcdefghijklmnprstuvwzyx23456789";
		//srand((double)microtime()* 1000000);
		$str = "";
		$i = 0;
		
		while($i < $length){
			$num = rand() % 33;
			$temp = substr($chars, $num, 1);
			$str = $str.$temp;
			$i++;
		}

		$_SESSION['captcha'] = $str;
	}

	private function registrationTimeStatus($startUnix, $endUnix) {
		$unix=round(microtime(true) * 1000);

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
}