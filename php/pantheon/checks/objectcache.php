<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;

class Objectcache extends Checkimplementation {
  public $check_all_plugins;

  public function init() {
    $this->action = 'No action required';
    $this->description = 'Checking the object caching is on and responding.';
    $this->score = 2;
    $this->result = '';
    $this->label = 'Object Cache';
    $this->alerts = array();
    self::$instance = $this;
    return $this;
  }

  public function run() {
    global $redis_server;
    $object_cache_file = ABSPATH.'/wp-content/object-cache.php';
    if (!file_exists($object_cache_file)) {
      $this->alerts[] = array("message"=> "No object-cache.php exists", "code" => 0);
    } else {
      $this->alerts[] = array("message"=> "object-cache.php exists", "code" => 2);
    }

    if (empty($redis_server)) {
      $this->alerts[] = array("message"=> "Using redis with WP-Redis object caching would speed up your backend.", "code" => 0);
    } else {
      $this->alerts[] = array("message"=> "Redis found", "code" => 2);
    }

    return $this;
  }

  public function message(Messenger $messenger) {
      if (!empty($this->alerts)) {
        $total = 0;
        $rows = array();
        $table = new \cli\Table();
        foreach ($this->alerts as $alert) {
          $total += $alert['code'];
          $label = 'ok';
          if (-1 === $alert['code']) {
            $label = 'warning';
          } elseif( 2 > $alert['code']) {
            $label = 'notice';
          }
          $table->addRow( array(
            'check' => $alert['message'],
            'status' => $label
          ));
        }

        $avg = $total/count($this->alerts);
        $this->result = "\n";
        $this->result .= join("\n", $table->getDisplayLines() );

        $this->score = $avg;
        $this->action = "You should use object caching";
    }
    $messenger->addMessage(get_object_vars($this));
  }
}
