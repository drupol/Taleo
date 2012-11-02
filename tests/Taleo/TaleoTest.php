<?php
namespace Taleo;

class TaleoTest extends \PHPUnit_Framework_TestCase {

  public function setUp() {
    $user = $password = $company = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890'),0,6);
    if (!file_exists('config.inc.php')) {
      $this->fail("Please create a config file 'config.inc.php' at the root of the project with \$user, \$password and \$company variables.\n");
    }
    include('config.inc.php');
    $this->config = new \stdClass();
    $this->config->user = $user;
    $this->config->password = $password;
    $this->config->company = $company;
  }

  /**
   * @covers Taleo\login()
   * @covers Taleo\logout()
   */
  public function testLoginLogout() {
    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);
    $taleo->logout();

    $name = sys_get_temp_dir().'/Taleo-';
    $count = count(glob($name.'*'));
    $this->assertEquals(0, $count);

    $token = $taleo->login();
    $files = glob($name.'*');
    $count = count($files);
    $this->assertEquals(1, $count);

    $file_content = file_get_contents($files[0]);
    $this->assertEquals($file_content, $token);
  }

  /**
   * @covers Taleo\login()
   */
  public function testLogin() {
    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);
    $taleo->logout();

    $name = sys_get_temp_dir().'/Taleo-';
    $token = $taleo->login();
    $this->assertNotEmpty($token);
    $files = glob($name.'*');
    $count = count($files);
    $this->assertEquals(1, $count);
    $file_content = file_get_contents($files[0]);
    $this->assertEquals($file_content, $token);
  }

  /**
   * @covers Taleo\logout()
   */
  public function testLogout() {
    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);
    $taleo->logout();
    $name = sys_get_temp_dir().'/Taleo-';
    $count = count(glob($name.'*'));
    $this->assertEquals(0, $count, 'No file ok.');
  }

  /**
   * @covers Taleo\get_host_url()
   */
  public function testHostUrl() {
    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);

    $url = sprintf($taleo->dispatcher_url, $taleo->taleo_api_version).'/'.$this->config->company;
    $request = $taleo->request($url);
    $response = json_decode($request);

    $this->assertNotEmpty($response->response->URL);
  }


}
