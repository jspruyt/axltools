<?php

/**
 * PHP Rest CURL
 * https://github.com/jmoraleda/php-rest-curl
 * (c) 2014 Jordi Moraleda
 */

Class RestCurl {
  public static function exec($method, $url, $obj = array(), $headerData = array()) {    
    
    $curl = curl_init();
     
    switch($method) {
      case 'GET':
        if(strrpos($url, "?") === FALSE) {
          $url .= '?' . http_build_query($obj);
        }
        break;

      case 'POST': 
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($obj));
        break;

      case 'PUT':
      case 'DELETE':
      default:
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method)); // method
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($obj)); // body
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);

    // Array $http_header data default
    $headerDataDefault = array('Accept: application/json', 'Content-Type: application/json');
    // Check headerData
    if (!empty($headerData)) {
        $headerDataDefault = array_merge($headerDataDefault, $headerData);
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headerDataDefault);
    
    // Exec
    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    
    // Data
    $header = trim(substr($response, 0, $info['header_size']));
    $body = substr($response, $info['header_size']);
     
    return array('status' => $info['http_code'], 'header' => $header, 'data' => json_decode($body));
  }

  public static function get($url, $obj = array(), $headerData = array()) {
     return RestCurl::exec("GET", $url, $obj);
  }

  public static function post($url, $obj = array(), $headerData = array()) {
     return RestCurl::exec("POST", $url, $obj);
  }

  public static function put($url, $obj = array(), $headerData = array()) {
     return RestCurl::exec("PUT", $url, $obj);
  }

  public static function delete($url, $obj = array(), $headerData = array()) {
     return RestCurl::exec("DELETE", $url, $obj);
  }
}