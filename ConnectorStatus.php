<?php
require_once("./lib/loader.php");

if (session_status() == PHP_SESSION_NONE) {
	session_start();
}

echo json_encode(AXLClient::getStatus());