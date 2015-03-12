<?php
namespace Pantheon;

use \Pantheon\Messenger,
      \Pantheon\Utils,
      \Pantheon\Checker;
use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Finder\Finder;

class Filesearcher extends Checker {
  private $finder; // symfony filesystem object
  private $dir;
  static $instance;

  public function __construct($dir) {
    $this->finder = new Finder();
    $this->dir = $dir;
    self::$instance = $this;
    return $this;
  }

  public function execute() {
    foreach($this->callbacks() as $class => $object) {
      $object->init();
    }

    $files = $this->finder->files()->in($this->dir)->name("*.php");
    foreach ( $files as $file ) {
      if (\WP_CLI::get_config('debug')) {
        \WP_CLI::line( sprintf("-> %s",$file->getRelativePathname()) );
      }

      foreach($this->callbacks() as $class => $object) {
        $object->run($file);
      }
    }

    foreach($this->callbacks() as $class => $object) {
        $object->message(Messenger::instance());
    }
  }

}
