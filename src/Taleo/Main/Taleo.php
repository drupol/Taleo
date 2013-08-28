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
use Guzzle\Plugin\Cookie\Cookie;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\FileCookieJar;
use Guzzle\Log\MessageFormatter;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;

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
   * @var string
   */
  private $host_url;

  /**
   * Pattern of the cookie file used to store the authentication cookie.
   * @var string
   */
  private $temporary_namefile = 'taleo_';

  /**
   * Guzzle client
   * @var \Guzzle\Service\Client
   */
  private $client;

  /**
   * User agent
   * @var string
   */
  private $agent = "Taleo PHP Library version 2.0";

  /**
   * Constructor where the Guzzle client is initialized,
   * the user agent is set and log system is by default
   * set to Alert, so, it doesn't log anything.
   *
   * @param $userName
   * @param $password
   * @param $orgCode
   */
  public function __construct($userName, $password, $orgCode) {
    $this->userName = $userName;
    $this->password = $password;
    $this->orgCode = $orgCode;

    $this->client = new Guzzle\Service\Client(array('ssl.certificate_authority' => FALSE));
    $this->client->setUserAgent($this->agent);

    // By default, the logger log only ALERT;
    // It can be changed by a call to the method loglevel($level) and
    // $level should be an integer, see the Monolog documentation.
    $this->setLogConfig(Logger::ALERT);
  }

  /**
   * Returns the cookie file name
   * @return string
   */
  public function getTempNamefile() {
    return $this->temporary_namefile;
  }

  /**
   * Get the Taleo endpoint URL.
   * @return string|bool
   */
  public function getHostUrl() {
    $url = sprintf($this->dispatcher_url, $this->taleo_api_version) . '/' . $this->orgCode;
    $this->client->setBaseUrl($url);
    $this->logger->log(LOGGER::INFO, 'Using Taleo API Version: ' . $this->taleo_api_version);

    if ($response = $this->request($url)) {
      $response = $response->json();
      $this->host_url = $response['response']['URL'];
      $this->logger->log(LOGGER::INFO, 'Taleo endpoint set to: ' . $this->host_url);
      return $this->host_url;
    }

    $this->logger->log(LOGGER::ERROR, 'Could not get Taleo endpoint.');
    return FALSE;
  }

  /**
   * Instead of saving the cookiePlugin in a class variable, use this method
   * to get it.
   *
   * @return bool|CookiePlugin
   */
  private function getCookiePluginObject() {
    $listeners = $this->client->getEventDispatcher()->getListeners('request.before_send');
    foreach($listeners as $listener) {
      if ($listener[0] instanceof Guzzle\Plugin\Cookie\CookiePlugin) {
        return $listener[0];
      }
    }
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
  public function setLogConfig($level = LOGGER::ALERT, $file = NULL) {
    $levels = array(
      LOGGER::DEBUG,
      LOGGER::INFO,
      LOGGER::WARNING,
      LOGGER::ERROR,
      LOGGER::CRITICAL,
      LOGGER::ALERT
    );

    if (!is_writable($file) && $file != 'php://stdout') {
      $file = sys_get_temp_dir() . '/Taleo.log';
    }

    if (!in_array($level, $levels)) {
      $level = LOGGER::ALERT;
    }

    // Remove LogPlugin.
    foreach(LogPlugin::getSubscribedEvents() as $key => $data) {
      $listeners = $this->client->getEventDispatcher()->getListeners($key);
      foreach($listeners as $listener) {
        foreach($listener as $event) {
          if ($event instanceof Guzzle\Plugin\Log\LogPlugin) {
            $this->client->getEventDispatcher()->removeSubscriber($event);
          }
        }
      }
    }

    // Add new LogPlugin
    $this->logger = new Logger('Taleo');
    $this->logger->pushHandler(new StreamHandler($file, $level));
    $adapter = new Guzzle\Log\PsrLogAdapter($this->logger);
    $this->client->addSubscriber(new LogPlugin($adapter));

    $this->logger->log(LOGGER::INFO, 'Setting log file to: ' . $file . ' at level ' . $this->logger->getLevelName($level) . '(' . $level . ')');
  }

  /**
   * Login method, returns TRUE on successful login or FALSE in any other
   * cases.
   *
   * @return bool
   */
  public function login() {
    // Check if we can retrieve the Taleo endpoint
    if ($host_url = $this->getHostUrl()) {
      $this->host_url = $host_url;
    } else {
      return FALSE;
    }

    // Check if we are already logged in.
    if ($this->isLoggedIn()) {
      $this->logger->log(LOGGER::INFO, "Login successful.");
      return TRUE;
    }

    // Loop through each cookie file and check which one is valid.
    // It will also delete the ones who are invalids.
    $name = sys_get_temp_dir() . '/' . $this->getTempNamefile();
    $valid_file = NULL;

    $files = glob($name . '*', GLOB_NOSORT);
    array_multisort(array_map('filemtime', $files), SORT_NUMERIC, SORT_DESC, $files);

    foreach ($files as $file) {
      $this->logger->log(LOGGER::INFO, 'Testing cookie file: ' . $file);
      $cookiePlugin = new CookiePlugin(new FileCookieJar($file));
      $this->client->addSubscriber($cookiePlugin);
      if ($response = $this->get('object/info')) {
        $this->logger->log(LOGGER::INFO, 'Valid cookie file found at: ' . $file);
        return TRUE;
      }
      $this->logger->log(LOGGER::INFO, 'Invalid cookie file found at: ' . $file);
      $this->client->getEventDispatcher()->removeSubscriber($cookiePlugin);
      unset($cookiePlugin);
      unlink($file);
    }

    $credentials = array(
      "userName" => $this->userName,
      "password" => $this->password,
      "orgCode" => $this->orgCode
    );

    // Now trying to login on Taleo using the credentials.
    if ($response = $this->request($this->host_url, 'login', 'POST', $credentials)) {

      // If no valie cookie file is found, let's create a new one.
      $valid_file = tempnam(sys_get_temp_dir(), $this->getTempNamefile());
      $this->logger->log(LOGGER::INFO, 'Initializing new cookie file at: ' . $valid_file);

      $response = $response->json();
      $parsed_url = parse_url($this->host_url);

      // Use Guzzle Plugin system to include the cookie on each request.
      $cookiePlugin = new CookiePlugin(new FileCookieJar($valid_file));
      $cookiePlugin->getCookieJar()->add(
        new Cookie(
          array(
            'name' => 'authToken',
            'value' => $response['response']['authToken'],
            'domain' => $parsed_url['host'],
            'expires' => time() + 4 * 60 * 60,
            'discard' => FALSE
          )
        )
      );
      $this->client->addSubscriber($cookiePlugin);

      $this->logger->log(LOGGER::INFO, 'Adding authentication cookie to cookie file.');
      $this->logger->log(LOGGER::INFO, 'Login successful.');
      return TRUE;
    } else {
      $this->logger->log(LOGGER::INFO, 'Unable to set cookie.');
      $this->logger->log(LOGGER::INFO, 'Login failed.');
      return $this->logout() ? FALSE : TRUE;
    }
  }

  /**
   * @param bool $value
   * @return bool
   */
  public function logout($value = TRUE) {
    if ($this->isLoggedIn()) {
      $this->logger->log(LOGGER::INFO, 'Logging out.');
      $this->post('logout');
      $this->client->getEventDispatcher()->removeSubscriber($this->getCookiePluginObject());
    }

    $this->logger->log(LOGGER::INFO, 'Logout successful.');
    return (bool) $value;
  }

  /**
   * Check if we are logged in or not.
   *
   * @return bool
   */
  public function isLoggedIn() {
    if (!($cookiePlugin = $this->getCookiePluginObject())) {
      return FALSE;
    }

    $cookie = $cookiePlugin->getCookieJar()->all(NULL, NULL, 'authToken', TRUE, TRUE);

    if (is_array($cookie) && count($cookie) >= 1) {
      if ($cookie[0] instanceof Guzzle\Plugin\Cookie\Cookie) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * @param $url
   * @param string $path
   * @param string $method
   * @param array $parameters
   * @param array $data
   * @return bool|Guzzle\Http\Message\Response
   */
  private function request($url, $path = '', $method = 'GET', $parameters = array(), $data = array()) {
    $method = strtoupper($method);

    $this->client->setBaseUrl($url);

    if ($path != 'login' && $path != '' && $path != 'object/info') {
      if (!$this->isLoggedIn()) {
        $this->logger->log(LOGGER::DEBUG, 'Could not execute this request without being logged in.');
        return FALSE;
      }
    }

    if ($method == 'GET') {
      $request = $this->client->get($path);
    }

    if ($method == 'POST') {
      $data = is_array($data) ? json_encode($data) : $data;
      $request = $this->client->post($path, NULL, $data);
    }

    if ($method == 'PUT') {
      $data = is_array($data) ? json_encode($data) : $data;
      $request = $this->client->put($path, NULL, $data);
    }

    if ($method == 'DELETE') {
      $request = $this->client->delete($path);
    }

    foreach ($parameters as $key => $value) {
      $request->getQuery()->set($key, $value);
    }

    switch ($path) {
      case 'login':
        break;

      default:
        $request->setHeader('Content-Type', 'application/json');
    }

    try {
      $response = $request->send();
    } catch (Guzzle\Http\Exception\BadResponseException $e) {
      return FALSE;
    }

    if (!is_object($request)) {
      return FALSE;
    }

    if (!$response->getHeader('Content-Type')->hasValue('application/json;charset=UTF-8')) {
      return FALSE;
    }

    return $response;
  }

  // Aliases
  /**
   * @param $path
   * @param array $parameters
   * @return bool|Guzzle\Http\Message\Response
   */
  public function get($path, $parameters = array()) {
    return $this->request($this->host_url, $path, 'GET', $parameters, array());
  }

  /**
   * @param $path
   * @param array $data
   * @param array $parameters
   * @return bool|Guzzle\Http\Message\Response
   */
  public function post($path, $data = array(), $parameters = array()) {
    return $this->request($this->host_url, $path, 'POST', $parameters, $data);
  }

  /**
   * @param $path
   * @return bool|Guzzle\Http\Message\Response
   */
  public function delete($path) {
    return $this->request($this->host_url, $path, 'DELETE');
  }

  /**
   * @param $path
   * @param array $data
   * @param array $parameters
   * @return bool|Guzzle\Http\Message\Response
   */
  public function put($path, $data = array(), $parameters = array()) {
    return $this->request($this->host_url, $path, 'PUT', $parameters, $data);
  }
}
