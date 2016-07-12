<?php
/*
 * Plugin Name: Domaincheck
 * Description: Adds a domaincheck module to contact form 7
 * Author: Jelmer Schuiteboer, ACServices
 */

class Domaincheck {
	const SETTING_SLUG = 'domaincheck-settings';
	
	private static $instance;
	
	private $option;
	
	private function __construct() {
		require_once 'dc-request.php';
		require_once 'dc-cf7-module.php';
		require_once 'dc-admin-page.php';
		require_once 'dc-admin-page-identity.php';
		
		new DC_WPCF7_Module();

		add_action('wp_ajax_' . 'check_domain',		array($this, 'handle_json_request'));
		add_action('wp_ajax_nopriv_' . 'check_domain',	array($this, 'handle_json_request'));

		new DC_Admin_Page();
		new DC_Admin_Page_Identity();
	}
	
	public function get_setting() {
		return array(
			'dc_request_url' => array(
				'id' => 'dc_request_url',
				'title' => 'Api request url',
			),
			'dc_login_name' => array(
				'id' => 'dc_login_name',
				'title' => 'Login name',
			),
			'dc_login_pass' => array(
				'id' => 'dc_login_pass',
				'title' => 'Password',
			),
			'dc_tld_list' => array(
				'id' => 'dc_tld_list',
				'title' => 'Tld list',
				'type' => 'textarea',
				'description' => 'Put one extension per line. Domains will be checked in the same order you enter them here.',
				'default' => join("\n", array('nl', 'com', 'eu', 'be', 'net', 'de', 'nu', 'frl', 'ph', 'yoga')),
			),
		);
	}
	
	public function get_setting_value($id) {
		$settings = $this->get_setting();
		
		if(!isset($settings[$id])) return null;
		
		if($this->option == null) {
			$this->option = get_option(Domaincheck::SETTING_SLUG);
		}
		
		if(isset($this->option[$id])) {
			return $this->option[$id];
		}
		
		if(isset($settings[$id]['default'])) {
			return $settings[$id]['default'];
		}		
	}

	/**
	 * Takes care of handling an ajax domain check request. Returns a json
	 * encoded array or 0.
	 * 
	 * Callback for the wp_ajax_nopriv_ hook (wp_ajax_nopriv_ requires the
	 * wp_ajax_ hook).
	 */
	public function handle_json_request() {
		ob_clean();

		if(isset($_POST['tld']) && isset($_POST['sld'])) {
			$request = new DC_Request_Domain_Check($_POST['sld'], $_POST['tld']);

			$echo = array();
			$echo['isAvailable'] = $request->is_available();
			$echo['message'] = $request->getStatusMessage();
			$echo['id'] = $_POST['id'];
			//$echo['debug'] = $request;

			@header('Content-Type: application/json; charset=' . get_option('blog_charset'));

			echo json_encode($echo);
		}

		die(0);
	}

	public static function getInstance() {
		if(!self::$instance) {
			self::$instance = new Domaincheck();
		}

		return self::$instance;
	}
}

Domaincheck::getInstance();