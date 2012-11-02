<?php
namespace Taleo\Entities;

class User implements Entity {

  private $user;

  public function __construct($data) {
    $this->user = $data;
  }

  public function get($key = NULL) {
    if (!is_null($key)) {
      return $this->user->$key;
    }
    return $this->user;
  }

  public function to_array() {
    return (array) $this->user;
  }

  public function to_json() {
    return json_encode($this->user);
  }

}
