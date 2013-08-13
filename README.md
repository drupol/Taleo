Taleo PHP Library
=================

What is Taleo (from Wikipedia) ?
================================
Taleo Corporation was a publicly traded provider of cloud-based talent management solutions headquartered in Dublin, California.
Taleo's solutions are primarily centered around talent acquisition (recruitment), performance management, learning and development, and compensation management.
These capabilities combine to provide what Taleo calls "Talent Intelligence" or an enhanced level of insight into candidates and employees.
Taleo sells its products entirely via a software-as-a-service (SaaS) model, in which all software and information resides in data centers operated and secured by Taleo.
As of August 2011, Taleo reported it had more than 5,000 customers ranging from small and medium-sized businesses to large global enterprises, including nearly half of the Fortune 100.
On February 9, 2012, Oracle Corporation entered into an agreement to acquire Taleo Corporation.

What is the Taleo PHP library
=============================
The Taleo PHP Library allow you to connect to the Taleo services using REST.
It allows you to retrieve data but also create and alter existing data.

Requirements
============
 * PHP >= 5.3.x
 * Guzzle, an HTTP client: http://guzzlephp.org/
 * Monolog, a logger: https://github.com/Seldaek/monolog

Installation
============
Taleo PHP Library is using composer (http://getcomposer.org/) to manage it's dependency.
To get started, install composer, then run the command: "composer install".
It will download the required library automatically and you'll be able to use Taleo PHP Library directly.

Documentation
=============
 * Where it all began: https://github.com/shoxty/Taleo/issues/1
 * Taleo REST API documentation: http://cl.ly/011B34322v0t/download/TBE_REST_API_GUIDE_v12_4.pdf

Examples
========

```php
<?php
include_once './vendor/autoload.php';
use \Taleo\Main\Taleo as Taleo;

$user = '******';
$password = '******';
$company = '******';

// When call the library with the valid parameters,
// a new token will be generated.
$taleo = new Taleo($user, $password, $company);

// See the Monolog documentation to check which levels are available.
// By default, Taleo PHP Library doesn't log anything (log level set to ALERT)
// except ALERT, triggered by errors.
// If you change this to DEBUG, it will log almost everything.
// By default, the logfile is in the default PHP temporary directory,
// Under the name of "Taleo.log"
// You can use a second parameter to define the file to use.
// You can also use 'php://stdout' to debug quickly.
// Do not forget to disable the DEBUG level when in Production !
$taleo->setLogConfig(\Monolog\Logger::DEBUG, 'php://stdout');

// A new token is generated.
// Mandatory if a logout() is made previously.
$taleo->login();

// Example of calls
// The default return format is an array converted from JSON.

/**
 * Requisitions
 */
$response = $taleo->get('object/requisition/search', array('status' => 'open', 'cws' => 1));
echo print_r($response,1)."\n";
$response = $taleo->get('object/requisition/1189');
echo print_r($response,1)."\n";

/**
 * Candidates
 */

// Retrieve the last candidates within the last 7 days.
$response = $taleo->get('object/candidate/search', array('status'=>1, 'addedWithin'=>7));
echo print_r($response,1)."\n";

// Create a candidate
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
echo print_r($response,1)."\n";

/**
 * Various
 */
$response = $taleo->get('object/info');

/**
 * Run the logout procedure
 * This is optional.
 */
$taleo->logout();

// If you call again your test file,
// it will use the last valid token available.
?>
```
TODOs:
======
 * Providing more examples,
 * Fixing bugs.
 * Run more tests with Travis,
 * Re organizing Taleo.php,
 * Provides helpers functions,
 * Comment all the function \o/.

Unit testing and continuous integration
=======================================
[![Build Status](https://secure.travis-ci.org/Polzme/Taleo.png)](http://travis-ci.org/Polzme/Taleo)

Taleo PHP Library is using Travis (https://travis-ci.org/), a continuous integration tool.
Each time a commit is made, Travis download the tree, run PHPUnit tests and a code analysis tools (PHP CodeSniffer).
If there's any error, the icon above turns red.
This is a good way to ensure a good quality of code.
If you plan to participate into the development, you can run those tools manually.

For PHPUnit, you have to copy the file phpunit.xml.dist to phpunit.xml, then run:
```
phpunit -c phpunit.xml
```
For PHPCS:
```
phpcs --standard=ruleset.phpcs.xml --encoding=UTF-8 --report=summary --ignore=*/vendor/* -p .
```

Thanks
======
 * Will Robertson (@shoxty)
