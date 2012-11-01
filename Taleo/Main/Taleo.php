<?php

/**
 * Taleo
 *
 * @package Taleo
 * @author Pol Dell'Aiera
 */

namespace Taleo\Main;
use Guzzle;

class Taleo {

  public $dispatcher_url = 'https://tbe.taleo.net/MANAGER/dispatcher/api/%1$s/serviceUrl/';
  public $taleo_api_version = 'v1';

  private $host_url;
  private $token;

  function __construct($username, $password, $company, $token = null) {
    $this->company = $company;
    $this->username = $username;
    $this->password = $password;

    $this->login($token);
  }

  private function endpoint($name) {
    return $this->host_url . $name;
  }

  public function login($token = null) {
    // The host url cannot be saved into a file, it can changes.
    $this->get_host_url();

    // The token is saved into a temporary file because you only have
    // a restricted amount of remote call per user per day.
    if (!is_null($token)) {
      $this->token = $token;
    } else {
      $this->token = $this->get_token();
    }
  }

  private function get_host_url() {
    $url = sprintf($this->dispatcher_url, $this->taleo_api_version).'/'.$this->company;
    $request = $this->request($url);
    $response = json_decode($request);
    $this->host_url = $response->response->URL;
  }

  private function get_token() {
    $name = sys_get_temp_dir().'/Taleo-';
    $data = array();

    foreach (glob($name.'*') as $file) {
      $timestamp = filemtime($file);
      $data[$timestamp] = $file;
    }

    krsort($data);
    $files = array_values($data);
    $timestamps = array_keys($data);

    $file = isset($files[0]) ? $files[0]:null;
    $timestamp = isset($timestamps[0]) ? $timestamps[0]:null;

    // According to the REST API Doc:
    // Token is valid only for 4 hours.
    if (!isset($file) OR (time() - (int)$timestamp - 4*60*60 > 0)) {
      $name = sys_get_temp_dir().'/Taleo-';
      foreach (glob($name.'*') as $file) {
        unlink($file);
      }

      $data = array(
        "orgCode" => $this->company,
        "userName" => $this->username,
        "password" => $this->password
      );

      $response = $this->request('login', 'POST', $data);
      $response = json_decode($response);
      $file = tempnam(sys_get_temp_dir(), 'Taleo-');
      file_put_contents($file, $response->response->authToken);
    }

    return file_get_contents($file);
  }

  private function get_client($url) {
    // Todo: Allow the user to set more option when initialisation
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
      if (isset($this->token)) {
        $data = array_merge($data, array('in0' => $this->token));
      }
      $request = $client->post($url, null, $data);
    }

    if (isset($this->token)) {
      $request->addCookie('authToken', $this->token);
    }

    try {
      $response = $request->send();
      $output = $response->getBody(true);
    } catch (Guzzle\Http\Exception\BadResponseException $e) {
      $output = json_decode($e->getResponse()->getBody(true));
      // TODO: Rework this.
      die("\n".$output->status->detail->errormessage."\n");
    }

    return $output;
  }

  // Aliases
  public function get($url, $data) {
    return $this->request($url, 'GET', $data);
  }

  public function post($url, $data) {
    return $this->request($url, 'POST', $data);
  }

  public function logout() {
    $this->request('logout', 'POST');
    $name = sys_get_temp_dir().'/Taleo-';
    foreach (glob($name.'*') as $file) {
      unlink($file);
    }
    unset($this->token);
  }

}
