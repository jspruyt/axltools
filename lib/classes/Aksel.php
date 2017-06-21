<?php

/**
 * Aksel is the templating class for AXL Tools.
 */
class Aksel {

	/**
	 * The parse function parses a template, replacing all variables with the given values.
	 *
	 * @param string $template string containing the template with variable placeholders using {% %} syntax
	 *
	 * @param array $data *associative array* containing the values that need to be parsed in the template
	 *
	 * @return string parsed template
	 */
	public static function parse($template, $data) {
		foreach ($data as $key => $value) {
			$template = preg_replace_callback("~%\s*(\w+)\s*(\|\s*(\w+(\([ ,A-Za-z0-9_-]+\))?))?\s*%~",
						function ($matches) use ($value, $debug) {							
							
							if (isset($matches[3])) {
								$value = Aksel::filter($value, $matches[3]);
							}
							return $value;
						}, $template);
		}
		return $template;
	}
	
	
	/**
	 * The filter function transforms the value of a variable according to a filter
	 *
	 * @param string $value string containing value to transform
	 *
	 * @param array $filter string describing the filter function (format <function name>[(argument1[, argument2])] 
	 *
	 * @return string transformed value
	 */
	private static function filter($value, $filter) {

		//function name
		$function = $filter;

		//argument array
		$arguments = array();
		$arguments[0] = $value;
		
		if (strpos($filter, '(')) {
			$filterArray = explode('(',str_replace(')', '', $filter));
			$function = $filterArry[0];

			$argString = $filterArray[1];
			$argString = trim($argString);
			$argumentArray = explode(',', $argString);
			
			foreach ($argumentArray as $argument) {
				$arguments[] = $argument;
			}
		}
		
		//check if filter function exists and call it
		if (method_exists("Aksel", $function)) {
			return call_user_func_array(array("Aksel", $function), $arguments);
		} else {
			return $value;
		}	
	}
	
	private static function upper($string) {
		return strtoupper($string);
	}
	
	private static function ascii($string) {
		$transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;', Transliterator::FORWARD);
		$transformed = $transliterator->transliterate($string);
		return $transformed;
	}
	
	private static function substr($string, $start, $length=null) {
		if ($length == null) {
			return substr($string, $start);
		} else {		
			return substr($string, $start, $length);
		}
	}
}