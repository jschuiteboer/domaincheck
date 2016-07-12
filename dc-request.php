<?php

abstract class DC_Request {
	public $result;
	
	public function __construct($command, $args) {
		$dc = Domaincheck::getInstance();
		
		$this->result = null;
			
		$args['command']     = (string) $command;
		$args['apiuser']     = $dc->get_setting_value('dc_login_name');
		$args['apipassword'] = 'MD5' . md5($dc->get_setting_value('dc_login_pass'));
		
		$url = add_query_arg($args, $dc->get_setting_value('dc_request_url'));
		
		$this->parse_response(wp_remote_get($url));		
	}
	
	/**
	 * Will return the the value for the specified key from the result or the
	 * default value if the key does not exist.
	 */
	public function getResult($key, $default = false) {		
		if(!isset($this->result[$key])) return $default;
		
		return $this->result[$key];
	}

	/**
	 * Will return the status code from the response. The status code is a
	 * string consisting of an xml code and a status code; 'XMLOK 10'. 
	 * If $part is 0, only the xml code will be returned.
	 * If $part is 1 then only the status code will be returned.
	 */
	public function getStatusCode($part = null) {
		$status = $this->getResult('status_code');
		
		if(!is_int($part) || $status == false) return $status;
		
		$status = explode(' ', $status, 2);
		
		if($part > 1) return false;
		
		return $status[$part];
	}
	
	/**
	 * Returns the response's status description.
	 */
	public function getStatusMessage($default = 'Onbekende status') {
		return $this->getResult('status_description', $default);
	}
	
	/**
	 * Returns true if the response did not contain an XMLOK code.
	 */
	public function is_error() {
		return $this->getStatusCode(0) !== 'XMLOK';
	}
	
	private function parse_response($response) {		
		if(!is_wp_error($response)) {
			// transform the xml string to an array
			$xml = simplexml_load_string($response['body']);
			$xml = json_decode(json_encode($xml), true);
			
			$this->result = $xml['order'];
		}
	}
}

class DC_Request_Domain_Check extends DC_Request {
	public function __construct($sld, $tld) {		
		parent::__construct('domain_check', array('tld' => $tld, 'sld' => $sld));
	}
	
	public function is_available() {
		// check for a XMLOK 11 code
		return $this->getStatusCode(1) === '11';
	}
}

class DC_Request_Add_Identity extends DC_Request {
	public function __construct($args) {		
		parent::__construct('identity_add', $args);
	}
	
	public function get_handle() {
		return $this->getResult('details');
	}
}