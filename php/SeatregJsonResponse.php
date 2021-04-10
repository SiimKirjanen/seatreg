<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class SeatregJsonResponse {
	public $_response;

    public function __construct(){
    	$this->_response = new stdClass();
        $this->_response->type = 'ok';
        $this->_response->text = null;
        $this->_response->data = null;
        $this->_response->extraData = null;
	}
	
	public function setData($dataToSend) {
		$this->_response->data = $dataToSend;
	}

	public function setError($errorText) {
		$this->_response->type = 'error';
		$this->_response->text = $errorText;
	}

	public function setValidationError($errorText) {
		$this->_response->type = 'validation-error';
		$this->_response->text = $errorText;
	}

	public function setText($text) {
		$this->_response->text = $text;
	}

	public function setExtraData($data) {
		$this->_response->extraData = $data;
	}
	
	public function echoData() {
		echo json_encode($this->_response);
	}
}