<?php
include_once './vendor/autoload.php';
use \Taleo\Main\Taleo as Taleo;

if (!file_exists('config.inc.php')) {
  die("Please create a config file 'config.inc.php' at the root of the project with \$user, \$password and \$company variables.\n");
}
include('config.inc.php');

$taleo = new Taleo($user, $password, $company);
$taleo->loglevel(\Monolog\Logger::DEBUG);

/*
// Optional
$taleo->login();

$response = $taleo->request('object/infos');
echo print_r($response,1)."\n";
$response = $taleo->request('object/requisition/search', 'GET', array('status' => 'open', 'cws' => 1));
echo print_r($response, 1)."\n";
$response = $taleo->request('object/requisition/1189');
echo print_r($response, 1)."\n";

// Optional
$taleo->logout();

$taleo->login();

$response = $taleo->request('object/info');
$response = $taleo->request('object/requisition/search', 'GET', array('status' => 'open', 'cws' => 1));
$response = $taleo->request('object/requisition/1189');

$taleo->logout();

$response = $taleo->get(
  'object/requisition/search',
  array('status' => 'open', 'cws' => 1)
);
$requisitions = new \Taleo\Collections\Collection($response);

$response = $taleo->get(
  'object/account/search'
);
$account = new \Taleo\Collections\Collection($response);

$response = $taleo->get(
  'object/candidate/search'
);
$candidate = new \Taleo\Collections\Collection($response);

$response = $taleo->get(
  'object/employee/search'
);
$employee = new \Taleo\Collections\Collection($response);

$response = $taleo->get(
  'object/user/search'
);
$user = new \Taleo\Collections\Collection($response);
*/

$taleo->logout();
?>
