<?php
namespace Taleo\Collections;

class Collection {

  private $objects = array();

  public function __construct($response = NULL) {
    if ($response === FALSE) {
      // TODO: When $response is false.
    }
    $data = json_decode($response);
    $results = $data->response->searchResults;

    foreach ($results as $data) {
      foreach ($data as $key => $values) {
        $this->add($key, $values);
      }
    }
  }

  public function add($entity, $data) {
    if ($entity == 'account') {
      $this->objects[] = new \Taleo\Entities\Account($data);
    }
    if ($entity == 'candidate') {
      $this->objects[] = new \Taleo\Entities\Candidate($data);
    }
    if ($entity == 'employee') {
      $this->objects[] = new \Taleo\Entities\Employee($data);
    }
    if ($entity == 'requisition') {
      $this->objects[] = new \Taleo\Entities\Requisition($data);
    }
    if ($entity == 'user') {
      $this->objects[] = new \Taleo\Entities\User($data);
    }
  }

  public function to_array() {
    $output = array();
    foreach($this->objects as $object) {
      $output[] = $object->to_array();
    }
    return $output;
  }

  public function __toString() {
    return json_encode($this->to_array());
  }

}
