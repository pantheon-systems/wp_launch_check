<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;

class Insecure extends Checkimplementation {

  public function init() {
    $this->action = 'We did not find any files running risky functions.';
    $this->description = 'PHP files running eval or base64_decode on user input can be insecure.';
    $this->score = 2;
    $this->result = '';
    $this->label = 'Risky PHP Functions';
    return $this;
  }

  public function run($file) {
    $regex = '.*(eval|base64_decode)\(.*';
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
      $details = sprintf( "Found %s files that reference risky function. \n\t-> %s",
        count($this->alerts),
        join("\n\t-> ", $this->alerts)
      );
      $this->score = 0;
      $this->result .= $details;
      $this->action = "You do not need to deactivate these files, but please scrutinize them in the event of a security issue.";
    }
    $messenger->addMessage(get_object_vars($this));
    return $this;
  }
}
