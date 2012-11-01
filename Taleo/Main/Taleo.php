<?php

/**
 * Taleo
 *
 * @package Taleo
 * @author Pol Dell'Aiera
 */

namespace Taleo\Main;
use Guzzle\Service\Client;

class Taleo {

  public $dispatcher_url = 'https://tbe.taleo.net/MANAGER/dispatcher/api/%1$s/serviceUrl/';
  public $taleo_api_version = 'v1';

  private $host_url;
  private $token;

  function __construct($username, $password, $company) {
    $this->company = $company;
    $this->username = $username;
    $this->password = $password;

    $this->connect();
  }

  public function endpoint($name) {
    return $this->host_url . $name;
  }

  public function connect() {
    // The host url cannot be saved into a file, it can changes.
    $this->get_host_url();
    // The token is saved into a temporary file because you only have
    // a restricted amount of remote call per user per day.
    $this->get_token();
  }

  private function get_host_url() {
    $url = sprintf($this->dispatcher_url, $this->taleo_api_version).'/'.$this->company;
    $request = $this->get_client($url);
    $response = $request->get()->send();
    $response = json_decode($response->getBody(true));

    if($response->status->success == 1) {
      $this->host_url = $response->response->URL;
    } else {
      throw new Exception($response->status->detail->errormessage);
    }
  }

  private function get_token() {
    $name = sys_get_temp_dir().'/Taleo-';
    $files = array();

    foreach (glob($name.'*') as $file) {
      $timestamp = filemtime($file);
      $files[$timestamp] = $file;
    }

    krsort($files);
    $file = array_shift($files);

    if (!isset($file)) {
      $data = array(
        "orgCode" => $this->company,
        "userName" => $this->username,
        "password" => $this->password
      );

      $response = $this->request('login', 'POST', $data);
      $response = json_decode($response);
      if($response->status->success == 1) {
        $file = tempnam(sys_get_temp_dir(), 'Taleo-');
        file_put_contents($file, $response->response->authToken);
      } else {
       throw new Exception($response->status->detail->errormessage);
      }

    }

    $this->token = file_get_contents($file);
    echo "Token found: ".$this->token."\n";

  }

  function get_client($url) {
    // Todo: Allow the user to set more option when initialisation
    return new Client($url, array(
      'ssl.certificate_authority' => false,
    ));
  }

  public function request($url, $method = 'GET', $data = array()) {

    if(strpos($url, "https://") === false) {
      $url = $this->endpoint($url);
    }

    $client = $this->get_client($url);

    if ($method == 'GET') {
      $request = $client->get($url);
      foreach ($data as $key => $value) {
        $request->getQuery()->set($key, $value);
      }
    }

    if ($method == 'POST') {
      if (isset($this->token)) {
        $data = array_merge($data, array('in0' => $this->token));
      }
      $request = $client->post($url, null, $data);
    }

    if (isset($this->token)) {
      $request->addCookie('authToken', $this->token);
    }
    return $request->send()->getBody(true);
  }

  // Aliases
  public function get($url, $data) {
    return $this->request($url, 'GET', $data);
  }

  public function post($url, $data) {
    return $this->request($url, 'POST', $data);
  }


}
