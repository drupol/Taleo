<?php

/**
 * @file
 * Demo file
 */

include './vendor/autoload.php';
use \Taleo\Main\Taleo as Taleo;

if (!file_exists('config.inc.php')) {
  die("Please create a config file 'config.inc.php' at the root of the project with \$user, \$password and \$company variables.\n");
}
include 'config.inc.php';

/**
 * Create the Taleo object with a valid user, password and company code.
 */
$taleo = new Taleo($user, $password, $company);

/**
 * Set the log configuration.
 *
 * @param int $level Logger level.
 *  \Monolog\Logger::DEBUG
 *  \Monolog\Logger::INFO
 *  \Monolog\Logger::WARNING
 *  \Monolog\Logger::ERROR
 *  \Monolog\Logger::CRITICAL
 *  \Monolog\Logger::ALERT
 * @param string $file Optional file.
 *  This can be a file or 'php://stdout'.
 */
$taleo->setLogConfig(\Monolog\Logger::DEBUG, 'php://stdout');

/**
 * Run the login procedure.
 * This is mandatory.
 */
$taleo->login();

/**
 * Requisitions
 */
//$response = $taleo->get('object/requisition/search', array('status' => 'open', 'cws' => 1));
//echo print_r(json_decode($response),1)."\n";
//$response = $taleo->get('object/requisition/1189');
//echo print_r(json_decode($response),1)."\n";

/**
 * Candidates
 */
// Retrieve the last candidates within the last 7 days.
//$response = $taleo->get('object/candidate/search', array('status'=>1, 'addedWithin'=>7));
//echo print_r(json_decode($response),1)."\n";
// Create a candidate
/*
$response = $taleo->post(
  'object/candidate',
  array(
    'candidate' =>
    array(
      'city' => 'Toontown',
      'country' => 'Be',
      'resumeText' => 'This is just a test using new TALEO API.',
      'email' => 'drupol@about.me',
      'firstName' => 'Pol',
      'lastName' => "Dell'Aiera",
      'status' => 2,
      'middleInitial' => 'P',
      'cellPhone' => '0123456789',
    )
  )
);
echo print_r(json_decode($response),1)."\n";
*/


/**
 * Various
 */
//$response = $taleo->get('object/info');


/**
 * Run the logout procedure
 * This is optional.
 */
$taleo->logout();
