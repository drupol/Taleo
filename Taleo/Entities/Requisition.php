<?php

namespace Taleo\Entities;

class Requisition implements \Entity {

  private $requisition;

  function __construct($data) {
    $this->requisition = $data;
  }

  function get($key = null) {
    if (!is_null($key)) {
      return $this->requisition->$key;
    }
    return $this->requisition;
  }

  function to_array() {
    return (array)$this->requisition;
  }

  function to_json() {
    return json_encode($this->requisition);
  }

}
