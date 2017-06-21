<?php

class Cluster {
	
	public $name;
	public $host;
	public $version;
	public $user;
	public $selected;
	
	public function __construct () {
	}
	
	public static function fromArray($r) {
		$instance = new self();
		$instance->loadFromArray($r);
		return $instance;
	}
	
	public static function fromValues($name, $host, $version, $user) {
		$instance = new self();
		$instance->loadFromValues($name, $host, $version, $user);
		return $instance;
	}
	
	public function toArray() {
		$r = array();
		$r['cluster'] = array();
		$r['cluster']['name'] = $this->name;
		$r['cluster']['host'] = $this->host;
		$r['cluster']['version'] = $this->version;
		$r['cluster']['user'] = $this->user;
		$r['cluster']['selected'] = $this->selected;
		return $r;
	}
	
	protected function loadFromArray($r) {
		$this->name = $r['cluster']['name'];
		$this->host = $r['cluster']['host'];
		$this->version = $r['cluster']['version'];
		$this->user = $r['cluster']['user'];
		$this->selected = "";
	}
	
	protected function loadFromValues($name, $host, $version, $user) {
		$this->name = $name;
		$this->host = $host;
		$this->version = $version;
		$this->user = $user;
		$this->selected = "";

	}
}
