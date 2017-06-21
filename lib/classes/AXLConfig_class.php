<?php


class AXLConfig {
	public $rows;
	private $axl;

	function __construct($rows) {
		$this->rows = $rows;
	}
	
	private function getArrayKeyValue($arr, $key) {
		$arrIt = new RecursiveIteratorIterator(new RecursiveArrayIterator($arr));

		 foreach ($arrIt as $sub) {
			$subArray = $arrIt->getSubIterator();
			if (array_key_exists($key, $subArray)) {
				return $subArray[$key];
			}
		}
		return "key not found";
	}
	
	public function getAscii($string) {
		
		//':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;'
		$transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;', Transliterator::FORWARD);
		$normalized = $transliterator->transliterate($string);
		
		return $normalized;
	}

	public function createConfig($axl, $template) {
		$this->axl = $axl;
		
		//WebOut::getInstance()->log("input array", WebOut::getInstance()->dumpVar($this->rows), "notice");
		//loop through all entries
		for ($i = 0; $i < count($this->rows); $i++) {
			$def = $template;
			$paramMessage = "";
			$parameters = $this->rows[$i];
			
			//replace variables
			foreach ($parameters as $key => $value) {
				$def = preg_replace_callback("/%(".$key.")(\|(\w*))?%/",
							function ($matches) use ($value) {							
								if (isset($matches[3])) {
									switch ($matches[3]) {
										case "upper":
											$value = strtoupper($value);
											break;
										case "ascii":
											$value = $this->getAscii($value);
											break;
									}								
								}
								return $value;
							}, $def);
				$paramMessage = $paramMessage."<span class='info'>".$key.": ".$value."</span><br/>";
			}

			WebOut::getInstance()->log("Variables in use", $paramMessage, "notice");

			//parse def to arrays
			$config = yaml_parse($def, -1);

			//create configuration elements
			foreach ($config as $item) {
				//skip the options section in the yaml file
				if (array_key_exists("variable_labels", $item)) {
					continue;
				}

				//flags
				$isMultiple = false;

				//check if there are options in the item
				if (array_key_exists("options", $item)) {
					//check for dependency
					if (array_key_exists("dependency", $item['options'])) {
						if (array_key_exists($item['options']['dependency'], $parameters)) {
							$dep_value = $parameters[$item['options']['dependency']];
							if (!($dep_value == "true" || $dep_value == "t")) {
								continue;
							}
						} else {
							continue;
						}
						
					}

					//check for multiple
					if (array_key_exists("multiple", $item['options'])) {
						$isMultiple = $item['options']['multiple'];
					}					
				}

				try {
					if ($isMultiple) {
						foreach ($item['arguments'] as $arg) {
							$arg_array = array($arg);
							$arg_keys = array_keys($arg_array[0]);
							$info = $this->getArrayKeyValue($arg_array, "debuginfo");
							$this->axlCall($item['function'], $arg_array, $info);
						}
					} else {
						$arg_array = array($item['arguments']);
						$arg_keys = array_keys($arg_array[0]);
						$info = $this->getArrayKeyValue($arg_array, "debuginfo");
						$this->axlCall($item['function'], $arg_array, $info);
					}
				} catch (SoapFault $sf) {
					WebOut::getInstance()->log("Timed Out", "Could not connect to host", "failed");
					break;
				}
			}
		}
	}
	
	

	private function axlCall($function_name, $arguments, $info="no name found") 
	{
		$starttime = microtime(true);
		$debug = WebOut::getInstance();

		try {
			if (substr($function_name,0,4) === 'cupi') 
			{
				//Cupi::importUserLegacy($function_name, $arguments);
				Cupi::__call($function_name, $arguments);


			} 
			else 
			{
			
				$response = null;
				$response = $this->axl->__soapCall($function_name, $arguments);
				//WebOut::getInstance()->log($function_name, WebOut::getInstance()->dumpVar($arguments), "notice");
				$message = " <strong>".$info."</strong>: <br/>";
				$message .= "RESULT: ".$response->return."<br/>";

				$endtime = microtime(true);
				$message .= "elapsed time: ".($endtime - $starttime)."<br/>";
				//WebOut::getInstance()->log($function_name, htmlentities($this->axl->__getLastRequest()), "notice");
				WebOut::getInstance()->log($function_name, $message, "success");
			}
		}
		catch (SoapFault $sf) {

			$message = " <strong>".$info."</strong>: <br/>";

			if (strpos($sf, 'Could not connect to host') > 1) {
				throw ($sf);
				exit();
			}

			if (strpos($sf, 'UNIQUE INDEX') == false && strpos($sf, 'exists with the same') == false) {
				$message .= "SoapFault: " . $sf . "<br/><br/>";
				$message .= "<pre>".var_export($arguments,true)."</pre>";
				$message .= "REQUEST: <br />" . htmlentities($this->axl->__getLastRequest()) . "<br />";
			} else {
				$message .= "Record already exists<br/>";
			}

			$endtime = microtime(true);
			$message .= "elapsed time: ".($endtime - $starttime)."<br/>";

			WebOut::getInstance()->log($function_name, $message, "failed");


		}
		catch (Exception $e) {
			echo "Exception: " . $e . "<br/><br/>";
		}

	}

}
