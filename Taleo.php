<?php

/**
 * Taleo
 *
 * @package Taleo
 * @author Pol Dell'Aiera
 */

class Taleo {

  public $dispatcher_url = "https://tbe.taleo.net/MANAGER/dispatcher/api/v1/serviceUrl/";
  static $instance;
  private static $client;

  function __construct($username, $password, $company) {
    self::$instance = &$this;

    $this->company = $company;
    $this->username = $username;
    $this->password = $password;

    $this->connect();
  }

  public function endpoint($name) {
    return $this->host_url . $name;
  }

  public function connect() {
    $this->get_host_url();
    $this->get_token();
  }

  private function get_host_url() {
    $url = $this->dispatcher_url.'/'.$this->company;
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
    $data = array(
      "orgCode" => $this->company,
      "userName" => $this->username,
      "password" => $this->password
    );

    $client = $this->get_client($this->endpoint('login'));
    $request = $client->post($this->endpoint('login'),null,$data);
    $response = json_decode($request->send()->getBody(true));

    $request->addCookie('authToken', $response->response->authToken);

    if($response->status->success == 1) {
      $this->token = $response->response->authToken;
    } else {
      throw new Exception($response->status->detail->errormessage);
    }
    echo "Token set to ".$this->token."\n";

  }

  function get_client($url) {
    return new Guzzle\Service\Client($url, array(
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
      $data = array_merge($data, array('in0' => $this->token));
      $request = $client->post($url, null, $data);
    }

    $request->addCookie('authToken', $this->token);
    $request->addHeader('content-type', 'application/json');
    return $request->send()->getBody(true);
  }
}
