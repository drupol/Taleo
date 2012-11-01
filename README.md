Taleo
=====

What is Taleo (from Wikipedia) ?
================================
Taleo Corporation was a publicly traded provider of cloud-based talent management solutions headquartered in Dublin, California.
Taleo's solutions are primarily centered around talent acquisition (recruitment), performance management, learning and development, and compensation management.
These capabilities combine to provide what Taleo calls "Talent Intelligence" or an enhanced level of insight into candidates and employees.
Taleo sells its products entirely via a software-as-a-service (SaaS) model, in which all software and information resides in data centers operated and secured by Taleo.
As of August 2011, Taleo reported it had more than 5,000 customers ranging from small and medium-sized businesses to large global enterprises, including nearly half of the Fortune 100.
On February 9, 2012, Oracle Corporation entered into an agreement to acquire Taleo Corporation.

What is the Taleo library in PHP
================================
Taleo allow you to request through an defined API.
Taleo is now slowly switching from a SOAP based API to a REST based API.
This library allows you to connect to Taleo and request data.

Requirements
============
 * PHP >= 5.3.x
 * Guzzle, an HTTP client for PHP: http://guzzlephp.org/

Documentation
=============
You can find the whole Taleo REST API documentation here: http://cl.ly/011B34322v0t/download/TBE_REST_API_GUIDE_v12_4.pdf
Issue with Will: https://github.com/shoxty/Taleo/issues/1

Examples
========

```php
<?php

require("./guzzle.phar");
require('Taleo.php');

$user = '******';
$password = '******';
$company = '******';

$taleo = new Taleo($user, $password, $company);
$response = $taleo->request('object/info');
echo print_r($response,1)."\n";

$response = $taleo->request('object/requisition/search', 'GET', array('status' => 'open', 'cws' => 1));
echo print_r($response, 1)."\n";

?>
```

Thanks
======
 * Will Robertson (@shoxty)
