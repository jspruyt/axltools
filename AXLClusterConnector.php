<?php
require_once("./lib/Twig/AutoLoader.php");
require_once("./lib/loader.php");


Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader, array('cache' => false));

$vArray = array();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$vArray['form'] = $_POST;
	
	if (isset($_POST['submit'])) {
		addCluster();
	}
}


$vArray['clusters'] = ClusterList::getClusters();
$vArray['webout'] = WebOut::getInstance()->readStack();
$vArray['title'] = 'Cluster Connector';
$vArray['cssdebugversion'] = time();


echo $twig->render('AXLClusterConnector.html', $vArray);