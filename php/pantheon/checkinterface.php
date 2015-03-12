<?php
namespace Pantheon;

interface Checkinterface {

  // set the default state of the test
  public function init();

  // run the test
  public function run();

}
