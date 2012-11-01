<?php

namespace Taleo\Collections;

class Requisitions {

  private $requisitions = array();

  function __construct($response = null) {
    $data = json_decode($response);
    $results = $data->response->searchResults;

    foreach ($results as $data) {
      $this->add(new \Taleo\Objects\Requisition($data->requisition));
    }
  }

  function add(\Taleo\Objects\Requisition $requisition) {
    $this->requisitions[] = $requisition->to_array();
  }

  function __toString() {
    return json_encode($this->requisitions);
  }

}
