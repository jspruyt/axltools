<?php
require_once("./lib/Twig/AutoLoader.php");
require_once("./lib/loader.php");


Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader, array('cache' => false));

$vArray = array();


if (session_status() == PHP_SESSION_NONE) {
	session_start();
}
	
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$vArray['form'] = $_POST;
	
	$data = csv_array($_POST['field1']);
	
	$vArray['result'] = Aksel::parse($_POST['field2'], $data[0]);
	
}

$vArray['webout'] = WebOut::getInstance()->readStack();
$vArray['title'] = 'AXL Tester';
$vArray['cssdebugversion'] = time();


echo $twig->render('AXLTester.html', $vArray);
