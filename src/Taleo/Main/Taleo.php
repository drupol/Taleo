<?php

/**
 * Taleo PHP Library
 *
 * @package Taleo
 * @author Pol Dell'Aiera
 */

namespace Taleo\Main;
use Guzzle;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Default Taleo PHP Library class.
 */
class Taleo {

  /**
   * The default Dispatcher URL.
   *
   * @var string
   */
  public $dispatcher_url = 'https://tbe.taleo.net/MANAGER/dispatcher/api/%1$s/serviceUrl';
  /**
   * The default Taleo API version.
   *
   * @var string
   */
  public $taleo_api_version = 'v1';

  /**
   * URL to query, set by method setHostUrl().
   * @see setHostUrl().
   * @var
   */
  private $host_url;
  /**
   * Token used in each query.
   *
   * @see login().
   * @var
   */
  private $token;
  /**
   * @var
   */
  private $logger;
  /**
   * @var
   */
  private $logger_level;
  /**
   * @var
   */
  private $logfile;

  /**
   * @param string $username
   * @param string $password
   * @param string $orgCode
   */
  public function __construct($userName, $password, $orgCode) {
    $this->userName = $userName;
    $this->password = $password;
    $this->orgCode = $orgCode;

    // By default, the logger log only ALERT;
    // It can be changed by a call to the method loglevel($level) and
    // $level should be an integer, see the Monolog documentation.
    $this->setLogConfig(Logger::ALERT);
  }

  /**
   * @param string $token Optional token.
   * @return bool|string
   */
  public function login($token = NULL) {
    // The host url cannot be saved into a file, it can changes.
    if ($host_url = $this->getHostUrl()) {
      $this->host_url = $host_url;
    } else {
      return FALSE;
    }

    // The token is saved into a temporary file because you only have
    // a restricted amount of remote call per user per day.
    if (is_null($token)) {
      $token = $this->getToken();
    }

    if ($token === FALSE) {
      $this->logger->AddAlert("Bad login/password.");
      return $this->logout(FALSE);
    }

    $this->token = $token;
    $this->logger->AddInfo("Login successful.", (array) $this->token);
    return $this->token;
  }

  /**
   * @return bool
   */
  public function logout($value = TRUE) {
    if (isset($this->token)) {
      $this->logger->AddInfo("Deleting token: " . $this->token);
      $this->post('logout');
    }
    $name = sys_get_temp_dir().'/Taleo-';
    foreach (glob($name.'*') as $file) {
      $this->logger->AddDebug("Deleting token file: " . $file);
      unlink($file);
    }
    unset($this->token);
    $this->logger->AddInfo("Logout successful.");
    return (bool) $value;
  }

  /**
   * @return string|bool
   */
  public function getHostUrl() {
    $url = sprintf($this->dispatcher_url, $this->taleo_api_version) . '/' . $this->orgCode;

    if ($request = $this->request($url)) {
      $this->host_url = json_decode($request)->response->URL;
      $this->logger->AddInfo("Using Taleo API Version: " . $this->taleo_api_version);
      $this->logger->AddInfo("Host url set to : " . $this->host_url);
      return $this->host_url;
    }

    $this->logger->AddAlert("Using Taleo API Version: " . $this->taleo_api_version);
    $this->logger->AddAlert("Impossible to get the host url, probably a bad company code.");
    return $this->logout(FALSE);
  }

  /**
   * @return bool|string
   */
  private function getToken() {
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
        "userName" => $this->userName,
        "password" => $this->password,
        "orgCode" => $this->orgCode
      );

      if ($response = $this->request($this->host_url, 'login', 'POST', $data, array())) {
        $response = json_decode($response);
        $file = tempnam(sys_get_temp_dir(), 'Taleo-');
        file_put_contents($file, $response->response->authToken);
        $this->logger->AddInfo("Token file is too old or unavailable. Creating a new one.");
      }
    }

    if (file_exists($file)) {
      $this->logger->AddInfo("Temporary token file: " . $file);
      return file_get_contents($file);
    }

    $this->logger->AddInfo("Unable to get a valid token.");
    return FALSE;
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
   * @param int $level Logger level.
   * @param string $file Optional file.
   */
  public function setLogConfig($level, $file = NULL) {
    $levels = array(
      LOGGER::DEBUG,
      LOGGER::INFO,
      LOGGER::WARNING,
      LOGGER::ERROR,
      LOGGER::CRITICAL,
      LOGGER::ALERT
    );

    $this->setLogFile($file);

    if (!in_array($level, $levels)) {
      $level = Logger::ALERT;
    }

    $streamhandler = new StreamHandler($this->logfile, $level);

    if (isset($this->logger_level)) {
      $this->logger->popHandler();
    } else {
      $this->logger = new Logger('Taleo');
    }

    $this->logger->pushHandler($streamhandler);
    $this->logger_level = $level;
    $this->logger->AddInfo("Setting logfile to: " . $this->logfile);
    $this->logger->AddInfo("Setting log level to: " . $this->logger_level . "(".LOGGER::getLevelName($this->logger_level).")");
  }

  /**
   * @param $file
   * @return bool
   */
  public function setLogFile($file) {
    if (!is_writable($file) && $file != 'php://stdout') {
      $file = sys_get_temp_dir() . '/Taleo.log';
    }

    $this->logfile = $file;
    return TRUE;
  }

  /**
   * @param $url
   * @param string $method
   * @param array $data
   * @return bool|\Guzzle\Http\EntityBodyInterface|string
   */
  private function request($url, $path = '', $method = 'GET', $parameters = array(), $data = array()) {

    $method = strtoupper($method);

    $client = new Guzzle\Service\Client($url, array(
      'ssl.certificate_authority' => FALSE,
    ));

    if ($method == 'GET') {
      $request = $client->get($path);
    }

    if ($method == 'POST') {
      $data = is_array($data) ? json_encode($data) : $data;
      $request = $client->post($path, NULL, $data);
    }

    if ($method == 'DELETE') {
      $request = $client->delete($path);
    }

    foreach ($parameters as $key => $value) {
      $request->getQuery()->set($key, $value);
    }

    switch ($path) {
      case 'login':
        break;

      default:
        if (isset($this->token)) {
          $request->addCookie('authToken', $this->token);
        }
        $request->setHeader('Content-Type', 'application/json');
    }

    $this->logger->AddInfo("Request ".$method.": ".$request->getUrl(), (array) $data);

    try {
      $response = $request->send();
    } catch (Guzzle\Http\Exception\BadResponseException $e) {
      //TODO: Need a better error handling.
      $code = $e->getRequest()->getResponse()->getStatusCode();
      $message = 'Error ' . $code;
      $status = (array) json_decode($e->getResponse()->getBody(TRUE))->status->detail;
      $this->logger->AddAlert($message, $status);
      return FALSE;
    }

    if (!is_object($request)) {
      //TODO: Need a better error handling.
      $this->logger->AddDebug("Error during processing.");
      return FALSE;
    }

    if (!$response->getHeader('Content-Type')->hasValue('application/json;charset=UTF-8')) {
      //TODO: Need a better error handling.
      $this->logger->addAlert("The Content-Type header is wrong.");
      return FALSE;
    }

    $output = $response->getBody(TRUE);
    $this->logger->AddDebug("Response: ". $output);

    return $output;
  }

  // Aliases
  /**
   * @param $url
   * @param array $data
   * @return bool|\Guzzle\Http\EntityBodyInterface|string
   */
  public function get($path, $parameters = array()) {
    if (!isset($this->token)) {
      $this->logger->AddInfo("Request GET: " . $path);
      $this->logger->AddDebug('Couldn\'t execute this request without being logged in.');
      return FALSE;
    }

    return $this->request($this->host_url, $path, 'GET', $parameters, array());
  }

  /**
   * @param $url
   * @param array $data
   * @return bool|\Guzzle\Http\EntityBodyInterface|string
   */
  public function post($path, $data = array(), $parameters = array()) {
    if (!isset($this->token)) {
      $this->logger->AddInfo("Request POST: " . $path);
      $this->logger->AddDebug('Couldn\'t execute this request without being logged in.');
      return FALSE;
    }
    return $this->request($this->host_url, $path, 'POST', $parameters, $data);
  }

  /**
   * @param $url
   * @param array $data
   * @return bool|\Guzzle\Http\EntityBodyInterface|string
   */
  public function delete($path) {
    if (!isset($this->token)) {
      $this->logger->AddInfo("Request DELETE: " . $path);
      $this->logger->AddDebug('Couldn\'t execute this request without being logged in.');
      return FALSE;
    }
    return $this->request($this->host_url, $path, 'DELETE');
  }
}
