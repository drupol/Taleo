<?php

interface Entity {

  public function get($key = null);
  public function to_array();
  public function to_json();

}
