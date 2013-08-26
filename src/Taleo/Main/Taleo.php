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

  private $temporary_namefile = 'taleo_';

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
   * @var \Guzzle\Service\Client
   */
  private $client;
  /**
   * @var
   */
  private $cookiePlugin;

  /**
   * @param string $username
   * @param string $password
   * @param string $orgCode
   */
  public function __construct($userName, $password, $orgCode) {
    $this->userName = $userName;
    $this->password = $password;
    $this->orgCode = $orgCode;

    $this->client = new Guzzle\Service\Client(array('ssl.certificate_authority' => FALSE));

    // By default, the logger log only ALERT;
    // It can be changed by a call to the method loglevel($level) and
    // $level should be an integer, see the Monolog documentation.
    $this->setLogConfig(Logger::ALERT);
  }

  public function getTempNamefile() {
    return $this->temporary_namefile;
  }


  /**
   * @param string $token Optional token.
   * @return bool|string
   */
  public function login() {
    // The host url cannot be saved into a file, it can changes.
    if ($host_url = $this->getHostUrl()) {
      $this->host_url = $host_url;
    } else {
      return FALSE;
    }

    $this->initializeCookie();

    if ($this->isLoggedIn()) {
      $this->logger->AddInfo("Login successful.");
      return TRUE;
    }

    $credentials = array(
      "userName" => $this->userName,
      "password" => $this->password,
      "orgCode" => $this->orgCode
    );

    if ($response = $this->request($this->host_url, 'login', 'POST', $credentials)) {
      $response = $response->json();
      $parsed_url = parse_url($this->host_url);

      //TODO: Do not use class variable for the cookieplugin.
      $this->cookiePlugin->getCookieJar()->add(
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

      $this->client->addSubscriber($this->cookiePlugin);
      $this->logger->AddInfo('Adding authentication cookie to cookie file.');
      $this->logger->AddInfo('Login successful.');
      return TRUE;
    } else {
      $this->logger->AddInfo('Unable to set cookie.');
      $this->logger->AddAlert('Login failed.');
      return $this->logout() ? FALSE : TRUE;
    }
  }

  /**
   * @return bool
   */
  public function logout($value = TRUE) {
    if ($this->isLoggedIn()) {
      $this->logger->AddInfo("Logging out.");
      $this->post('logout');
      $this->cookiePlugin->getCookieJar()->remove(null, null, 'authToken');
    }

    $this->logger->AddInfo("Logout successful.");
    return (bool) $value;
  }

  /**
   * @return string|bool
   */
  public function getHostUrl() {
    $url = sprintf($this->dispatcher_url, $this->taleo_api_version) . '/' . $this->orgCode;

    $this->client->setBaseUrl($url);

    $this->logger->AddInfo('Using Taleo API Version: ' . $this->taleo_api_version);

    if ($response = $this->request($url)) {
      $response = $response->json();
      $this->host_url = $response['response']['URL'];
      $this->logger->AddInfo('Host url set to : ' . $this->host_url);
      return $this->host_url;
    }

    $this->logger->AddAlert('Could not get host url.');
    return $this->logout(FALSE);
  }

  private function initializeCookie() {
    // Loop through each cookie file and check which one is valid.
    $name = sys_get_temp_dir() . '/' . $this->getTempNamefile();

    $files = glob($name . '*', GLOB_NOSORT);
    array_multisort(array_map('filemtime', $files), SORT_NUMERIC, SORT_DESC, $files);

    foreach ($files as $timestamp => $file) {
      $this->logger->AddInfo("Testing cookie file: " . $file);
      $this->cookiePlugin = new CookiePlugin(new FileCookieJar($file));
      $this->client->addSubscriber($this->cookiePlugin);
      if ($response = $this->get('object/info')) {
        $this->logger->AddInfo("Valid cookie file found at " . $file);
        return TRUE;
        break;
      }
      $this->client->getEventDispatcher()->removeSubscriber($this->cookiePlugin);
      unset($this->cookiePlugin);
      unlink($file);
    }

    $file = tempnam(sys_get_temp_dir(), $this->getTempNamefile());
    $this->cookiePlugin = new CookiePlugin(new FileCookieJar($file));
    $this->logger->AddInfo("Initializing new cookie file at " . $file);
    return TRUE;
  }

  public function isLoggedIn() {
    if ($cookie = $this->getAuthCookie()) {
      return TRUE;
    }

    return FALSE;
  }

  public function getAuthCookie() {
    if (!($this->cookiePlugin instanceof Guzzle\Plugin\Cookie\CookiePlugin)) {
      return FALSE;
    }

    $cookie = $this->cookiePlugin->getCookieJar()->all(null, null, 'authToken', true, true);

    if (is_array($cookie) && count($cookie) >= 1) {
      if ($cookie[0] instanceof Guzzle\Plugin\Cookie\Cookie) {
        return $cookie[0];
      }
    }
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

    $this->client->setBaseUrl($url);

    if ($path != 'login' && $path != '' && $path != 'object/info') {
      if (!$this->isLoggedIn()) {
        $this->logger->AddDebug('Couldn\'t execute this request without being logged in.');
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
      $this->logger->AddInfo('Request ' . $method . '(' . $response->getStatusCode() . '): ' . $request->getUrl(), (array) $data);
    } catch (Guzzle\Http\Exception\BadResponseException $e) {
      //TODO: Need a better error handling.
      $response = $e->getRequest()->getResponse();
      $status = json_decode($response->getBody(TRUE));
      $code = $status->status->detail->errorcode;
      $message = $code . ': ' . $status->status->detail->errormessage;
      $this->logger->AddError($message);
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

    $this->logger->AddDebug("Response: ". $response->getBody(TRUE));
    return $response;
  }

  // Aliases
  /**
   * @param $url
   * @param array $data
   * @return bool|\Guzzle\Http\EntityBodyInterface|string
   */
  public function get($path, $parameters = array()) {
    return $this->request($this->host_url, $path, 'GET', $parameters, array());
  }

  /**
   * @param $url
   * @param array $data
   * @return bool|\Guzzle\Http\EntityBodyInterface|string
   */
  public function post($path, $data = array(), $parameters = array()) {
    return $this->request($this->host_url, $path, 'POST', $parameters, $data);
  }

  /**
   * @param $url
   * @return bool|\Guzzle\Http\EntityBodyInterface|string
   */
  public function delete($path) {
    return $this->request($this->host_url, $path, 'DELETE');
  }

  /**
   * @param $url
   * @param array $data
   * @return bool|\Guzzle\Http\EntityBodyInterface|string
   */
  public function put($path, $data = array(), $parameters = array()) {
    return $this->request($this->host_url, $path, 'PUT', $parameters, $data);
  }


}
