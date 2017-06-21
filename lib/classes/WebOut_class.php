<?php

class DebugMessage {
	
	public $title = "EMPTY";
	public $message = "EMPTY";
	public $type = "failed";
	
	public function __construct($title, $message, $type) {
		$this->title = $title;
		$this->message = $message;
		$this->type = $type;
	}
}

class WebOut {
	
	private $messageStack;
	
	private static $instance = null;

	public static function getInstance() {

		if (self::$instance === null) {
			self::$instance = new self();		
		}
		
		return self::$instance;
	}
	
	protected function __construct() {
		$this->messageStack = array(); 
	}

	public function log($title, $message, $type) {
		array_push($this->messageStack, new DebugMessage($title, $message, $type));
	}
	
	public function logWrite($title, $message, $type) {
		$this->log($title, $message, $type);
		$this->write();
	}
	
	public function write() {
		foreach ($this->messageStack as $message) {
			echo "<div class='webOut ".$message->type."'>";
			echo "<h3>".$message->title."</h3>";
			echo "<p>".$message->message."</p>";
			echo "</div>";			
		}
		
		
	}
	
	public function readStack() {
		$temp = $this->messageStack;
		$this->flush();
		return $temp;
	}
	
	public function flush() {
		$this->messageStack = array();
	}
	
	public function read() {
		return $this->messageStack;
	}
	
	public static function dumpVar($var) {
		ob_start();
		var_dump($var);
		$result = ob_get_clean();
		return $result;
	}
}
