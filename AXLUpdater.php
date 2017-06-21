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

	if (isset($_POST['csv']) && !empty($_POST['csv']) && isset($_POST['yaml']) && !empty($_POST['yaml'])) {
		startUpdater($_POST['csv'], $_POST['yaml']);
	} 
} elseif (isset($_SESSION['loginReferPost'])) {
	$_POST = $_SESSION['loginReferPost'];
	
	$_SESSION['loginReferPost'] = null;
	$_SESSION['loginRefer'] = null;
	
	startUpdater($_POST['csv'], $_POST['yaml']);
}

$vArray['webout'] = WebOut::getInstance()->readStack();
$vArray['title'] = 'AXL Updater';
$vArray['cssdebugversion'] = time();


echo $twig->render('AXLUpdater.html', $vArray);
