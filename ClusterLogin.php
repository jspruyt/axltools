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

	//load cluster indicated in form
	$c = ClusterList::getCluster($_POST['cluster']);
	
	$_SESSION['axlpass'] = $_POST['password'];
	$_SESSION['cluster'] = $c;
	
	//test connection
	AXLClient::getClient();
	if (AXLClient::testConnection()) {
		//return to referer
		$_SESSION['connected'] = true;
		if (isset($_SESSION['loginRefer'])) {
			WebOut::getInstance()->log("Connection succeeded", "successfully connected to ".$c->host, "success");
			$page = str_replace("/axltools/", "", $_SESSION['loginRefer']);
			header("Location: ".$page);
			exit;
		}
		WebOut::getInstance()->log("Connection succeeded", "successfully connected to ".$c->host, "success");
	} else {
		session_unset();
		session_destroy();
		$_SESSION['connected'] = false;
		WebOut::getInstance()->log("Error", "unable to connect to ".$c->host, "failed");
	}
}

$vArray['clusters'] = fillSelect('connector');
$vArray['webout'] = WebOut::getInstance()->readStack();
$vArray['title'] = 'Cluster Login';
$vArray['cssdebugversion'] = time();


echo $twig->render('ClusterLogin.html', $vArray);