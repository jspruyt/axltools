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

	if (isset($_POST['query'])) {
		$vArray['results'] = requestSQL($_POST['query'], $_POST['queryType']);
	} 
}

$vArray['webout'] = WebOut::getInstance()->readStack();
$vArray['title'] = 'AXL Query';
$vArray['cssdebugversion'] = time();


echo $twig->render('AXLQuery.html', $vArray);
