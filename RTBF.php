<?php
require_once("./lib/Twig/AutoLoader.php");
require_once("./lib/loader.php");


Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader, array('cache' => false));

$vArray = array();

$vArray['tools'] = getTools('RTBF');
$vArray['title'] = "RTBF Tools";
$vArray['cssdebugversion'] = time();

echo $twig->render('index.html', $vArray);