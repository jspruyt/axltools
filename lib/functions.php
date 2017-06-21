<?php

function getLine($line, $partition) {
	$client = AXLClient::getClient();
	
	try {
		$result = $client->getLine(
				array('pattern'=>"\\".$line,
					'routePartitionName'=>$partition,
					'returnedTags'=> array('pattern' => '',
											'routePartitionName'=>'',
											'description'=>'')
					)
				);
		return $result;
	}
	catch (SoapFault $sf) {
		if (strpos($sf, 'The specified Line was not found') == true) {
			return null;
		} else {
			WebOut::getInstance()->log("SOAP FAULT", $sf, "failed");
			return null;
		}
	}
	catch (Exception $e) {
		WebOut::getInstance()->log("ERROR", "Failed to activate ".$line."<br/>".$e, "failed");
		return null;
	}
}


function verifyLine($line) {
	if (getLine($line, 'Internal')) {
		return $line." est déjà active";
	} else if (getLine($line, 'Internal-Inactive')) {
		return $line." n'est pas encore activé";
	}
	
	return $line." pas trouvé";	
}

function activateLine($line) {

	$client = AXLClient::getClient();
	$short = substr($line, -4);
	
	try {
		$result = $client->updateLine(array('pattern'=>"\\".$line,'routePartitionName'=>'Internal-Inactive', 'newRoutePartitionName'=>'Internal'));
		WebOut::getInstance()->log($line, "La ligne est active maintenant", "success");
		
		$sc = "utilise les commandes suivantes pour désactiver le poste sur Siemens<br/><br/>";
		$sc .= "<span class='large monospace'>REG-SBSCU:{$short};<br/>";
		$sc .= "DEACT-DSSU:DI,STNO,{$short};<br/>";
		$sc .= "DEL-SBSCU:{$short},ALL;<br/>";
		$sc .= "CHA-WABE:{$short},120;<br/>";
		$sc .= "<br/></span>";
		
		WebOut::getInstance()->log("Commandes Siemens AMO", $sc, "notice");
	}
	catch (SoapFault $sf) {
		if (strpos($sf, 'Directory Number not found') == true) {
			WebOut::getInstance()->log("ERROR", "Failed to activate ".$line."<br/>Check if the DN exists or is active already", "failed");
		} else {
			WebOut::getInstance()->log("ERROR", "Failed to activate ".$line."<br/>".$sf, "failed");
		}	
	}
	catch (Exception $e) {
		WebOut::getInstance()->log("ERROR", "Failed to activate ".$line."<br/>".$e, "failed");
	}
}


function getTools($prefix = 'AXL') {
	$list = glob("./".$prefix."?*.php");

	foreach ($list as $key => $name) {
		$name = str_replace("./", "", $name);
		$name = str_replace(".php", "", $name);

		$list[$key] = $name;
	}

	return $list;
}

function startUpdater($csv, $yaml) {

	$axl = AXLClient::getClient();

	$input = array_map("str_getcsv", preg_split("/((\r?\n)|(\r\n?))/", trim($csv)));

	array_walk($input, function(&$a) use ($input) {
      $a = array_combine($input[0], $a);
    });
    $headers = array_shift($input);


	foreach($input as $record) {
		try {
			//set to original
			$def = $yaml;
			$message = "";

			//replace variables
			foreach ($headers as $header) {
				$def = str_replace("%".$header."%", $record[$header], $def);
				$message = $message . $header . ": " . $record[$header] . "<br/>";
			}

			//parse config
			$config = yaml_parse($def, -1);

			foreach($config as $item) {
				$response = $axl->__soapCall($item['function'], array($item['arguments']));
				WebOut::getInstance()->log($item['function']." successful", $message , "success");
			}
			sleep(1);
		}
		catch (SoapFault $sf) {
			WebOut::getInstance()->log("Failed to execute ".$item['function'], $message.$sf, "failed");
		}
		catch (Exception $e) {
			WebOut::getInstance()->log("Failed to execute ".$item['function'], $message.$e, "failed");
		}
	}

}


function setOwners($csv) {

	$client = AXLClient::getClient();

	try {

		foreach(preg_split("/((\r?\n)|(\r\n?))/", trim($csv)) as $line) {
			if (strpos($line, ',') !== false) {
				$keyvaluepair = explode(",", $line);
				$device = $keyvaluepair[0];
				$owner = $keyvaluepair[1];

				$result = $client->updatePhone(array('name'=>$device,'ownerUserName'=>$owner));
				WebOut::getInstance()->log($device." changed", "owner set to ".$owner, "success");
			}
		}
	}
	catch (SoapFault $sf) {
		WebOut::getInstance()->log("ERROR", "Failed to change ".$device."<br/>".$sf, "failed");
	}
	catch (Exception $e) {
		WebOut::getInstance()->log("ERROR", "Failed to change ".$device."<br/>".$e, "failed");
	}
}

function _combine_array(&$row, $key, $header) {
  $row = array_combine($header, $row);
}


function csv_array($input) {
	//$param = array();
	//foreach(preg_split("/((\r?\n)|(\r\n?))/", trim($input)) as $line) {
	//	if (strpos($line, ',') !== false) {
	//		$keyvalue = explode(",", $line);
	//		$param[$keyvalue[0]] = $keyvalue[1];
	//	}
	//}	

	//return $param;
	$input = trim($input);
	$array = array_map('str_getcsv', explode("\n", $input));
	$header = array_shift($array);
	array_walk($array, '_combine_array', $header);
	return $array;
}

function generateForm() {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if ($_POST['def'] != "0") {
			if (isset($_POST['generate'])) {
				$def = getYaml();
				$parameterMatches;
				$param = array();
				$variables = array();
				$labels = array();

				// get all parameters
				if (preg_match_all("/%(\w*)(\|\w*)?%/", $def, $parameterMatches) > 0) {

					foreach ($parameterMatches[1] as $match) {
						if (!in_array($match, $param)){
							array_push($param, $match);
						}
					}

				}

				//get dependencies
				if (preg_match_all("/dependency: ([\w]+)/", $def, $parameterMatches) > 0) {

					WebOut::getInstance()->log("Dependencies in file", WebOut::dumpVar($parameterMatches) , "notice");

					foreach ($parameterMatches[1] as $match) {
						$parameter = $match;

						if (!in_array($parameter, $param)){
							array_push($param, $parameter);
						}
					}
				}

				//get full names for variables from the yaml file
				//the variables should be in the first document in the yaml file
				$label_definition = yaml_parse($def, 0);

				foreach ($label_definition['variable_labels'] as $l) {
					$labels[$l['name']] = $l['label'];
				}

				WebOut::getInstance()->log("Labels", WebOut::dumpVar($labels) , "notice");

				foreach ($param as $p) {
					$var = array();
					$var['name'] = $p;
					$var['value'] = isset($_POST[$p])?$_POST[$p]:"";
					$var['label'] = isset($labels[$p])?$labels[$p]:$p;
					array_push($variables, $var);
				}

				return array ('variables' => $variables );

			} else {
				return array('csv' => isset($_POST['parameters'])?$_POST['parameters']:"");
			}

		}
	}
}


function addCluster() {
	$cluster = Cluster::fromValues($_POST['clustername'], $_POST['host'], $_POST['version'], $_POST['axluser']);
	ClusterList::addCluster($cluster);
	$_POST = array();
}

function fillSelect($item) {
	switch ($item) {
		case "def":

			$defFiles = glob("defs/*.yml");
			$defs = array();

			if (isset($defFiles)) {
				foreach ($defFiles as $d) {

					$path = explode("/", $d);
					$selected = "";

					if ($_SERVER['REQUEST_METHOD'] == 'POST') {
						if ($path[1] == $_POST['def']) {
							$selected = "selected";
						}
					}

					$defs[] = array ( 'name' => $path[1], 'selected' => $selected);

				}
				return $defs;
			}
			break;
		case "connector":
			$clusters = ClusterList::getClusters();

			if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'GET') {
				if (isset($clusters)) {
					foreach ($clusters as $key => $c) {

						if (isset($_GET['cluster'])) {

							if ($c->name == $_GET['cluster']) {
								$c->selected = 'selected';
								$clusters[$key] = $c;
							}

						} elseif (isset($_POST['cluster'])) {

							if ($c->name == $_POST['cluster']) {
								$c->selected = 'selected';
								$clusters[$key] = $c;
							}

						}

					}
				}
			}

			return $clusters;


		break;
	}
}

function getYaml() {
	return file_get_contents("defs/".$_POST['def']);
}

function startConfig() {
		$def = getYaml();
		$rows = array();

		if (isset($_POST['parameters'])) {
			$rows = csv_array($_POST['parameters']);
		} else {
			$rows[0] = $_POST;
		}

		try {
			$config = new AXLConfig($rows);
			$config->createConfig(AXLClient::getClient(), $def);
		} catch (Exception $e) {
			echo $e->getMessage();
			echo "caught";
			exit();
		}


}

function handleFileUpload() {
	$targetDir = "defs/";
	$targetFile = $targetDir . $_FILES['newDef']['name'];
	$fileType = pathinfo($targetFile,PATHINFO_EXTENSION);
	$uploadOk = 1;
	$debug = WebOut::getInstance();

	// Allow certain file formats
	if($fileType != "yml") {
		$debug->log("File upload failed", "Only .yml files are allowed", "failed");
		$uploadOk = 0;
	} else if (file_exists($targetFile)) {
		$debug->log("File upload failed", "The file already exists", "failed");
		$uploadOk = 0;
	}

	if ($uploadOk) {
		if (move_uploaded_file($_FILES["newDef"]["tmp_name"], $targetFile)) {
			$debug->log("File upload successful", "The file ". basename( $_FILES["newDef"]["name"]). " has been uploaded.", "success");
		} else {
			$debug->log("File upload failed", "There was a problem uploading your file", "failed");
		}
	}
}

/**
 *  outputCSV creates a line of CSV and outputs it to browser    
 */
function outputCSV($array) {
    $fp = fopen('php://output', 'w'); // this file actual writes to php output
    fputcsv($fp, $array);
    fclose($fp);
}
 
/**
 *  getCSV creates a line of CSV and returns it. 
 */
function getCSV($array) {
    ob_start(); // buffer the output ...
    outputCSV($array);
    return ob_get_clean(); // ... then return it as a string!
}


function requestSQL($sql, $queryType) {
	$debug = WebOut::getInstance();

	$client = AXLClient::getClient();
	$results= "";	
	
	try {
		
		
		if ($queryType == "select") {
			$response = $client->ExecuteSQLQuery(array("sql"=>$sql));
			$debug->log("debug", $debug->dumpVar($response->return), "notice");
			
			if(property_exists($response->return, "row")) {
				if (count($response->return->row) == 1) {
					$results = count($response->return->row) . " result\n";
					$results .= "------------------------------------------\n";
					$results .= getCSV(array_keys((array)$response->return->row));
					$results .= getCSV((array)$response->return->row);
				} else {
					$results = count($response->return->row) . " results\n";
					$results .= "------------------------------------------\n";
					$results .= getCSV(array_keys((array)$response->return->row[0]));
					foreach ($response->return->row as $record) {
						$results .= getCSV((array)$record);
					}
				}
				
			
				return $results;
			} else {
				$results = "no records match the query";
			}
		} else if ($queryType == "update") {
			
			$response = $client->ExecuteSQLUpdate(array("sql"=>$sql));
			//$debug->log("debug", $debug->dumpVar($response->return), "notice");
			$results = "Rows updated: " . $response->return->rowsUpdated;
			
		}
		
		
		
		
	}
	catch (SoapFault $sf) {
		WebOut::getInstance()->log("ERROR", $sf, "failed");
		$results = ("SOAP Fault\n");
		$results .= ("{$sf}");
		
	}
	catch (Exception $e) {
		WebOut::getInstance()->log("ERROR", $e, "failed");
		$results = ("Error\n");
		$results .= ("{$e}");
	}
	
	return $results;
}
