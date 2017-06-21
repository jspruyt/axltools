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


$vArray['defs'] = fillSelect("def");
$vArray['formgen'] = generateForm();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {	
	if ($_FILES['newDef']['error'] == 0) {
		handleFileUpload();
	} else if (isset($_POST['submit'])){
		startConfig();
	}
	
} elseif (isset($_SESSION['loginReferPost'])) {
	$_POST = $_SESSION['loginReferPost'];
	
	$_SESSION['loginReferPost'] = null;
	$_SESSION['loginRefer'] = null;
}

$vArray['webout'] = WebOut::getInstance()->readStack();
$vArray['title'] = 'Configuration Creator';
$vArray['cssdebugversion'] = time();


echo $twig->render('AXLConfigCreator.html', $vArray);