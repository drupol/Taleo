<?php
namespace Taleo\Entities;

class Employee implements Entity {

  private $employee;

  public function __construct($data) {
    $this->employee = $data;
  }

  public function get($key = NULL) {
    if (!is_null($key)) {
      return $this->employee->$key;
    }
    return $this->employee;
  }

  public function to_array() {
    return (array) $this->employee;
  }

  public function to_json() {
    return json_encode($this->employee);
  }

}
