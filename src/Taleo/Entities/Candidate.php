<?php
namespace Taleo\Entities;

class Candidate implements Entity {

  private $candidate;

  function __construct($data) {
    $this->candidate = $data;
  }

  public function get($key = null) {
    if (!is_null($key)) {
      return $this->candidate->$key;
    }
    return $this->candidate;
  }

  public function to_array() {
    return (array) $this->candidate;
  }

  public function to_json() {
    return json_encode($this->candidate);
  }

}
