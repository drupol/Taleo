<?php
namespace Taleo;

// Testing with a config.inc.php file.
class TaleoWConfigTest extends \PHPUnit_Framework_TestCase {

  public function setUp() {
    date_default_timezone_set('Europe/Brussels');

    $this->config = new \stdClass();
    $user = $password = $company = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890'), 0, 6);
    if (!file_exists('config.inc.php')) {
      $this->markTestSkipped(
        'Skipping those tests.'
      );
    } else {
      include 'config.inc.php';
    }
    $this->config->user = $user;
    $this->config->password = $password;
    $this->config->company = $company;

    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);
    $taleo->login();
    if (!$taleo->isLoggedIn()) {
      $this->markTestSkipped(
        'Bad credentials.'
      );
    }
    $taleo->logout();
  }

  public function testLoginLogout() {
    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);
    $taleo->login();
    $taleo->logout();

    $name = sys_get_temp_dir() . '/' . $taleo->getTempNamefile();
    $this->assertFalse($taleo->isLoggedIn());

    $taleo->login();
    $files = glob($name.'*');
    $count = count($files);
    $this->assertGreaterThanOrEqual(1, $count);
    $this->assertTrue($taleo->isLoggedIn());
  }

  public function testLogin() {
    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);
    $taleo->login();
    $taleo->logout();

    $taleo->login();
    $name = sys_get_temp_dir() . '/' . $taleo->getTempNamefile();
    $files = glob($name.'*');
    $count = count($files);
    $this->assertTrue($taleo->isLoggedIn());
    $this->assertGreaterThanOrEqual(1, $count);
  }

  public function testLogout() {
    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);
    $taleo->login();
    $taleo->logout();
    $this->assertFalse($taleo->isLoggedIn());
  }

  public function testHostUrl() {
    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);

    $url1 = $taleo->getHostUrl();
    $url2 = filter_var($url1, FILTER_VALIDATE_URL);

    $this->assertEquals($url1, $url2);
  }

  public function testCandidateCreationDeletion() {
    $taleo = new \Taleo\Main\Taleo($this->config->user, $this->config->password, $this->config->company);
    $taleo->login();

    $random_mail = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890'), 0, 6) . '@about.com';

    $message = $taleo->post(
      'object/candidate',
      array(
        'candidate' =>
        array(
          'city' => 'Toontown',
          'country' => 'Be',
          'resumeText' => 'This is just a test using new TALEO API.',
          'email' => $random_mail,
          'firstName' => 'Pol',
          'lastName' => "Dell'Aiera",
          'status' => 2,
          'middleInitial' => 'P',
          'cellPhone' => '0123456789',
        )
      )
    )->json();

    // Check if candidate has been successfully created.
    $this->assertTrue($message['status']['success']);

    // Get the candidate ID.
    $candId = $message['response']['candId'];

    // Check if the Candidate ID is numeric.
    $this->assertTrue(is_numeric($candId));

    $firstName = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890'), 0, 10);

    // Update candidate firstName
    $response = $taleo->put(
     'object/candidate/'.$candId,
      array(
        'candidate' => array(
          'firstName' => $firstName
        )
      )
    );

    // Get candidate with firstName...
    $message = $taleo->get('object/candidate/search', array('firstName' => $firstName))->json();

    // Check if there is only one result.
    $this->assertEquals($message['response']['pagination']['total'], 1);

    // Get the candidate object.
    $candidate = $message['response']['searchResults'][0]['candidate'];

    // Check if the retrieved value is equal to the $firstName.
    $this->assertEquals($candidate['firstName'], $firstName);

    // Delete the candidate.
    $message = $taleo->delete('object/candidate/'.$candId)->json();

    // Check if candidate has been successfully deleted.
    $this->assertTrue($message['status']['success']);
  }

}
