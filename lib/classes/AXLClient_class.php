<?php

class AXLClient {

	private static $instance = null;
	private static $client = null;
	private static $cluster = null;

	public static function getInstance() {
	
		if (self::$instance === null) {
			self::$instance = new self();		
		}
		
		return self::$instance;
	}

	
	public static function getClient() {
		if (self::$instance === null) {
			self::$instance = new self();		
		} 		
		return self::$client;
	}
	
	public static function testConnection() {			

		$returnedTags = array ( "name" => "");
		$searchCriteria = array ( "name" => "%");
		
		try {
			$result = self::getClient()->listProcessNode(array("returnedTags" => $returnedTags, "searchCriteria" => $searchCriteria));
			return true;
		} catch (SoapFault $sf) {
			WebOut::getInstance()->log("Failed to connect to server", $sf, "failed");
		} catch (Exception $e) {
			WebOut::getInstance()->log("Failed to connect to server", $e, "failed");
		}
		
		return false;
	}
	
	public static function getStatus() {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		
		if (!isset($_SESSION['axlpass']) || !isset($_SESSION['cluster'])) {
			return array ("status" => "unknown", "message" => "no cluster selected");
		}

		if (self::testConnection()) {
			return array ("status" => "on-line", "message" => "connected to ".self::$cluster->name);
		} else {
			return array ("status" => "off-line", "message" => self::$cluster->name." not reachable");
			return self::$cluster->name." not reachable";
		}
	}
	
	protected function __construct() {
		//start session
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		
		//check if cluster and password exist in session, if not redirect to login page
		if (!isset($_SESSION['axlpass']) || !isset($_SESSION['cluster'])) {
			
			$_SESSION['loginReferPost'] = $_POST;
			$_SESSION['loginRefer'] = $_SERVER['REQUEST_URI'];
			
			$get = isset($cluster)?"?cluster=".$cluster->name:"";
			
			header ("Location: ClusterLogin.php".$get);
			exit;
		} 
		
		//get pass and cluster from session
		$password = $_SESSION['axlpass'];
		self::$cluster = $_SESSION['cluster'];
		
		
		//create context
		$context = stream_context_create(array(
				'ssl' => array(
						 'verify_peer' => false,
						 'verify_peer_name' => false,
						 'allow_self_signed' => true
						)
		));
		
		//create client		
		self::$client = new SoapClient("./CUCM/".self::$cluster->version."/AXLAPI.wsdl",
			array('trace'=>true,
		   'exceptions'=>true,
		   'location'=>"https://".self::$cluster->host.":8443/axl",
		   'login'=> self::$cluster->user,
		   'password'=> $password,
		   'stream_context' => $context,
		   'connection_timeout' => 5
		));
	}
}

		
		


