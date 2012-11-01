<?php
namespace Taleo\Collections;

class Requisitions implements Collection {

  private $requisitions = array();

  function __construct($response = null) {
    $data = json_decode($response);
    $results = $data->response->searchResults;

    foreach ($results as $data) {
      $this->add(new \Taleo\Entities\Requisition($data->requisition));
    }
  }

  public function add($requisition) {
    $this->requisitions[] = $requisition->to_array();
  }

  public function __toString() {
    return json_encode($this->requisitions);
  }

}
