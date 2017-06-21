<?php
require_once("./lib/Twig/AutoLoader.php");
require_once("./lib/loader.php");


Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader, array('cache' => false));

$vArray = array();
$vArray['activate_enabled'] = "disabled";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$vArray['form'] = $_POST;
	
	if (isset($_POST['verify'])) {
		$vArray['result'] = verifyLine($_POST['line']);
		if (strpos($vArray['result'], "n'est pas encore activé") == true) {
			$vArray['activate_enabled'] = "enabled";
		}
	}
	
	if (isset($_POST['activate'])) {
		activateLine($_POST['line']);
	}
}


$vArray['clusters'] = ClusterList::getClusters();
$vArray['webout'] = WebOut::getInstance()->readStack();
$vArray['title'] = 'RTBF Activate Line';
$vArray['cssdebugversion'] = time();


echo $twig->render('RTBFactivateLine.html', $vArray);