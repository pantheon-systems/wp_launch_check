<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;

class Sessions extends Checkimplementation {
  public $name = 'sessions';

  public function init() {
    $this->action = 'You should install the Native PHP Sessions plugin - https://wordpress.org/plugins/wp-native-php-sessions/';
    $this->description = 'Sessions only work with sessions plugin is enabled';
    $this->score = 2;
    $this->result = '';
    $this->label = 'PHP Sessions';
    $this->has_plugin = class_exists("Pantheon_Sessions");
    return $this;
  }

  public function run($file) {
    if ($this->has_plugin) return;
    $regex = '.*(session_start|SESSION).*';
    preg_match('#'.$regex.'#s',$file->getContents(), $matches, PREG_OFFSET_CAPTURE );
    if ( $matches ) {
      $note = '';
      if (count($matches) > 1) {
        array_shift($matches);
      }
      foreach ($matches as $match) {
        $note .= sprintf(" [ Line %d, Match: '%s']", $match[1] + 1, substr($match[0],0,50) );
      }
      $this->alerts[] = $file->getRelativePathname().$note;
    }
    return $this;
  }

  public function message(Messenger $messenger) {
    if (!empty($this->alerts)) {
        $details = sprintf( "Found %s files that reference sessions \n\t-> %s",
        count($this->alerts),
        join("\n\t-> ", $this->alerts )
      );
      $this->score = -1;
      $this->result .= $details;
    } else {
      if ( $this->has_plugin ) {
        $details = 'You are running wp-native-php-sessions plugin. No scan needed';
      } else {
        $details = 'No files found referencing sessions.';
      }
      $this->result .= $details;
    }
    $messenger->addMessage(get_object_vars($this));
  }

}
