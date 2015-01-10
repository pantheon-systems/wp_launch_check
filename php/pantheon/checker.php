<?php
namespace Pantheon;

use \Pantheon\Messenger,
      \Pantheon\Utils;

class Checker {
  protected $callbacks; // array of objects and their callbacks
  static $instance;

  public function __construct() {
    self::$instance = $this;
    return $this;
  }

  public static function instance() {
    if (self::$instance)
      return self::$instance;
    return new Filesearcher();
  }

  public function register( $object ) {
    $this->callbacks[get_class($object)] = $object;
  }

  public function execute() {
    foreach($this->callbacks as $class => $object) {
      $object->init();
    }

    foreach($this->callbacks as $class => $object) {
      $object->run($file);
    }

    foreach($this->callbacks as $class => $object) {
        $object->message(Messenger::instance());
    }
  }

  protected function callbacks() {
    return $this->callbacks;
  }
}
