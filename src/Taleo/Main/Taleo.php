<?php

/**
 * Taleo
 *
 * @package Taleo
 * @author Pol Dell'Aiera
 */

namespace Taleo\Main;
use Guzzle;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Taleo\Exceptions\TaleoException;

class Taleo {

  public $dispatcher_url = 'https://tbe.taleo.net/MANAGER/dispatcher/api/%1$s/serviceUrl';
  public $taleo_api_version = 'v1';

  private $host_url;
  private $token;
  private $logger;
  private $logger_level;

  public function __construct($username, $password, $company, $token = NULL) {
    $this->company = $company;
    $this->username = $username;
    $this->password = $password;

    // By default, the logger log only ALERT;
    // It can be changed by a call to the method loglevel($level) and
    // $level should be an integer, see the Monolog documentation.
    $this->loglevel(Logger::DEBUG);

    // Do the login.
    $this->login($token);
  }

  public function login($token = NULL) {
    // The host url cannot be saved into a file, it can changes.
    $this->get_host_url();

    // The token is saved into a temporary file because you only have
    // a restricted amount of remote call per user per day.
    if (!is_null($token)) {
      $this->token = $token;
    } else {
      $this->token = $this->get_token();
    }

    $this->logger->AddInfo("Login in, token set to : " . $this->token);
    return $this->token;
  }

  private function get_host_url() {
    $url = sprintf($this->dispatcher_url, $this->taleo_api_version).'/'.$this->company;
    if ($request = $this->request($url)) {
      $response = json_decode($request);
      $this->host_url = $response->response->URL;
      $this->logger->AddInfo("Using Taleo API Version: " . $this->taleo_api_version);
      $this->logger->AddInfo("Host url set to : " . $this->host_url);
    }
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

    $file = isset($files[0]) ? $files[0]:NULL;
    $timestamp = isset($timestamps[0]) ? $timestamps[0]:NULL;

    // According to the REST API Doc:
    // Token is valid only for 4 hours.
    if (!isset($file) OR (time() - (int) $timestamp - 4 * 60 * 60 > 0)) {
      $name = sys_get_temp_dir().'/Taleo-';
      foreach (glob($name.'*') as $file) {
        unlink($file);
      }

      $data = array(
        "orgCode" => $this->company,
        "userName" => $this->username,
        "password" => $this->password
      );

      if ($response = $this->post('login', $data)) {
        $response = json_decode($response);
        $file = tempnam(sys_get_temp_dir(), 'Taleo-');
        file_put_contents($file, $response->response->authToken);
        $this->logger->AddInfo("Token file is too old or unavailable.");
      }
    }

    $this->logger->AddInfo("Temporary token file: " . $file);
    return file_exists($file) ? file_get_contents($file) : FALSE;
  }

  private function get_client($url) {
    // Todo: Allow the user to set more option when initialisation
    return new Guzzle\Service\Client($url, array(
      'ssl.certificate_authority' => FALSE,
    ));
  }

  /**
   * Set logger level.
   *  DEBUG => 100
   *  INFO => 200
   *  WARNING => 300
   *  ERROR => 400
   *  CRITICAL => 500
   *  ALERT => 550
   *
   * @param $level
   */
  public function loglevel($level) {
    $levels = array(
      LOGGER::DEBUG,
      LOGGER::INFO,
      LOGGER::WARNING,
      LOGGER::ERROR,
      LOGGER::CRITICAL,
      LOGGER::ALERT
    );

    if (!in_array($level, $levels)) {
      $level = Logger::ALERT;
    }

    $streamhandler = new StreamHandler($this->get_log_file(), $level);

    if (isset($this->logger_level)) {
      $this->logger->popHandler();
    } else {
      $this->logger = new Logger('Taleo');
    }

    $this->logger->pushHandler($streamhandler);
    $this->logger_level = $level;
    $this->logger->AddInfo("Setting log level to: " . $this->logger_level . "(".LOGGER::getLevelName($this->logger_level).")");
  }

  public function get_log_file() {
    return sys_get_temp_dir().'/Taleo.log';
  }

  public function request($url, $method = 'GET', $data = array()) {

    if(strpos($url, "https://") === FALSE) {
      $url = $this->host_url . '/' . $url;
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
      $request = $client->post($url, NULL, $data);
    }

    if (isset($this->token)) {
      $request->addCookie('authToken', $this->token);
    }

    $this->logger->AddInfo("Request ".$method.": ".$request->getUrl());

    try {
      $response = $request->send();
      $output = $response->getBody(TRUE);
      $this->logger->AddDebug("Response: ". $output);
    } catch (Guzzle\Http\Exception\BadResponseException $e) {
      return FALSE;
    }

    if (!is_object($request)) {
      return FALSE;
    }

    if (!$response->getHeader('Content-Type')->hasValue('application/json;charset=UTF-8')) {
      $this->logger->addAlert("The Content-Type header is wrong.");
      $this->logger->addAlert($output);
      return FALSE;
    }

    return $output;
  }

  // Aliases
  public function get($url, $data = array()) {
    return $this->request($url, 'GET', $data);
  }

  public function post($url, $data = array()) {
    return $this->request($url, 'POST', $data);
  }

  public function logout() {
    $this->logger->AddInfo("Logging out, deleting token: " . $this->token);
    $this->request('logout', 'POST');
    $name = sys_get_temp_dir().'/Taleo-';
    foreach (glob($name.'*') as $file) {
      $this->logger->AddDebug("Deleting token file: " . $file);
      unlink($file);
    }
    unset($this->token);
  }

}
