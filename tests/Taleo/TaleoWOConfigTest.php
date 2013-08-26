<?php
namespace Taleo;

// Testing without a config.inc.php file.
class TaleoWOConfigTest extends \PHPUnit_Framework_TestCase {

  public function setUp() {
    date_default_timezone_set('Europe/Brussels');

    $user = $password = $company = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890'), 0, 6);
    $this->config = new \stdClass();
    $this->config->user = $user;
    $this->config->password = $password;
    $this->config->company = $company;
  }

  public function testBadLogin() {
    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);
    $taleo->login();
    //$this->assertFalse($taleo->isLoggedIn());
  }

}
