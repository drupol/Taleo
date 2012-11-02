<?php
namespace Taleo\Entities;

interface Entity {

  public function get($key = NULL);
  public function to_array();
  public function to_json();

}
