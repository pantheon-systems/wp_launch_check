<?php
namespace Pantheon;

use \Pantheon\Messenger,
      \Pantheon\Utils;
use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Finder\Finder;

class Filesearcher {
  private $finder; // symfony filesystem object
  private $callbacks; // array of objects and their callbacks
  private $dir;
  static $instance;

  public function __construct($dir) {
    $this->finder = new Finder();
    $this->dir = $dir;
    echo $dir;die();
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

    $files = $this->finder->files()->in($this->dir)->name("*.php");
    foreach ( $files as $file ) {
      if (\WP_CLI::get_config('debug')) {
        \WP_CLI::line( sprintf("-> %s",$file->getRelativePathname()) );
      }

      foreach($this->callbacks as $class => $object) {
        $object->run($file);
      }
    }

    foreach($this->callbacks as $class => $object) {
        $object->message(Messenger::instance());
    }
  }

  public function callbacks() {
    return $this->callbacks;
  }


}
