<?php

class CupiApiException extends Exception
{   
    public function __construct($message, $code = 0, Exception $previous = null) {
        // some code
    
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class Cupi {

	$HOST_NAME = $GLOBALS['CUPI_HOST_NAME'];
	$USERNAME = $GLOBALS['CUPI_USERNAME'];
	$PASSWORD = $GLOBALS['CUPI_PASSWORD'];
    
    private static $CUPI_RESPONSE_CODES = array (
            '200'=> 'OK - Normal response when a page has been successfully fetched.',
            '201'=> 'Created - The resource has been created.',
            '301'=> 'Moved Permanently - The page has moved permanently. It is usually a response from implementing a 301 redirect.',
            '302'=> 'Moved Temporarily - The page has moved temporarily.',
            '400'=> 'Bad Request - The request could not be understood by the server, due to incorrect syntax.',
            '401'=> 'Unauthorized User - Authentication is required.',
            '403'=> 'Forbidden - The server understood the request, but is refusing to fulfill it.',
            '404'=> 'Page Not Found - The server has not found anything that matches the Request-URI.',
            '405'=> 'Method Not Allowed - The method specified in the Request-Line is not allowed for the resource identified by the Request-URI.',
            '406'=> 'Not Acceptable - The server cannot generate a response that the requester is willing to accept.',
            '410'=> 'Gone - The requested resource is no longer available at the server, and no forwarding address is known.',
            '415'=> 'Unsupported Media Type - The server is refusing the request, because the request is in a format not supported by the requested resource for the requested method.',
            '500'=> 'Server Error - There is an internal web server error.');
    
    public static function getImportUser($alias) 
    {
        $auth = base64_encode($USERNAME.":".$PASSWORD);
        $url = "https://{$HOST_NAME}/vmrest/import/users/ldap";        
        
        $obj['query'] = "(alias is {$alias})";
        $obj['limit'] = 1;

        $header = array("Authorization : Basic ".$auth);

        $result = RestCurl::get($url, $obj, $header);

        if ($result['status'] == "200") 
        {
            return $result['data']['ImportUser'];
        } else {
            throw new CupiApiException($CUPI_RESPONSE_CODES[$result['status']], $result['status']);
        }
    }

    public static function importUser($alias, $dtmfAccessId, $template) {
        $auth = base64_encode($USERNAME.":".$PASSWORD);
        $template = "RTBFvoicemail";
        
        $url = "https://{$HOST_NAME}/vmrest/import/users/ldap?templateAlias={$template}";        
        
        $obj['pkid'] = self::getImportUser($alias)['pkid'];
        $obj['alias'] = $alias;
        $obj['dtmfAccessId'] = $dtmfAccessId;

        $header = array("Authorization : Basic ".$auth);

        $result = RestCurl::post($url, $obj, $header);


        if ($result['status'] == "201") 
        {
            return true;
        } else {
            throw new CupiApiException($CUPI_RESPONSE_CODES[$result['status']], $result['status']);
        }

    }

    public function __call($functionName, $arguments) {			
        return call_user_func_array(Cupi::class, $functionName, $arguments);		
	}	


    public static function importUserLegacy($functionName, $arguments) {
        $function_name = substr($function_name,4);
        $message = "finding user {$arguments[0]['alias']}<br />";
        
        $ch = curl_init();
        
        $query = curl_escape($ch, '(alias is ' . $arguments[0]['alias'] . ')');
        $url = "https://{$HOST_NAME}/vmrest/import/users/ldap?limit=1&query={$query}";				
        
        //$fp = fopen(dirname(__FILE__).'/errorlog.txt', 'w');
        //curl_setopt($ch, CURLOPT_VERBOSE, 1);
        //curl_setopt($ch, CURLOPT_STDERR, $fp);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);				
        curl_setopt($ch, CURLOPT_USERPWD, "{$HOST_NAME}:{$PASSWORD}");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $message .= "HTTP {$httpcode}<br />";
        
        
        $user_array = json_decode($response, true);	
        if ($user_array['@total'] == 1) {
            $message .= "USER FOUND<br />";
            $arguments[0]['pkid'] = $user_array['ImportUser']['pkid'];
            
            $message .= "importing user {$arguments[0]['alias']}<br />";
            
            $url = 'https://{$HOST_NAME}/vmrest/import/users/ldap?templateAlias=RTBFvoicemail';
            $data_string = json_encode($arguments);
            WebOut::getInstance()->log($function_name, $data_string, "notice");
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_USERPWD, "{$HOST_NAME}:{$PASSWORD}");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
                'Content-Length: ' . strlen($data_string))                                                                       
            );
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            //if ($response === false) {
            //	$response = curl_error($ch);
            //}
            curl_close($ch);
            
            $message .= "HTTP {$httpcode}<br />";
            $message .= "response: {$response}<br />";
            
            if ($httpcode == "201") {
                $result = "success";
            } else {
                $result = "failed";
            }
            
            WebOut::getInstance()->log($function_name, $message, $result);
        } else {
            $message .= "USER NOT FOUND OR ALREADY IMPORTED<br />";
            WebOut::getInstance()->log($function_name, $message, "failed");					
        }

    }
    
    
}