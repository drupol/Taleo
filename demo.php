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
 * Optional: Set the log configuration.
 * To update the settings, you just have to call the method with
 * updated parameters.
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
 *
 */
$taleo->setLogConfig(\Monolog\Logger::DEBUG, 'php://stdout');

/**
 * Mandatory: Run the login procedure.
 */
$taleo->login();

/**
 * Optional: Update the logging configuration
 */
$taleo->setLogConfig(\Monolog\Logger::DEBUG, 'php://stdout');

/**
 * Requisitions
 */
/*
$response = $taleo->get('object/requisition/search', array('status' => 'open', 'cws' => 1))->json();
$response = $taleo->get('object/requisition/1189')->json();
*/

/**
 * Create a candidate
 */
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
      'firstName' => 'Polo',
      'lastName' => "Dell'Aiera",
      'status' => 2,
      'middleInitial' => 'P',
      'cellPhone' => '0123456789',
    )
  )
);
*/

/**
 * Search a candidate
 */
/*
$response = $taleo->get('object/candidate/search', array('email' => 'drupol@about.me'))->json();
$candid = $response['response']['searchResults'][0]['candidate']['candId'];
*/

/**
 * Update a candidate
 */
/*
$response = $taleo->put(
  'object/candidate/'.$candid,
  array(
    'candidate' =>
    array(
      'firstName' => 'Pol',
    )
  )
);
*/

/**
 * Delete a candidate
 */
/*
$response = $taleo->delete(
  'object/candidate/' . $candid
  );
*/

/**
 * Various
 */
//$response = $taleo->get('object/info');

/**
 * Optional: run the logout procedure
 */
//$taleo->logout();

