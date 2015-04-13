<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Insecure extends Checkimplementation {

  public function init() {
    $this->name = 'insecure';
    $this->action = 'We did not find any files running risky functions.';
    $this->description = 'PHP files running eval or base64_decode on user input can be insecure.';
    $this->score = 0;
    $this->result = '';
    $this->label = 'Risky PHP Functions';
    return $this;
  }

  public function run($file) {
    $regex = '(eval|base64_decode)\(.*';
    $file_contents = $file->getContents();
    preg_match('#'.$regex.'#', $file_contents, $matches, PREG_OFFSET_CAPTURE );
    if ( $matches ) {
      $note = '';
      foreach($matches as $match) {
        $linenum = substr_count(substr($file_contents, 0, $match[1]), "\n") + 1;
        $this->alerts[] = array( 'class'=>'warning', 'data'=> array( $file->getRelativePathname(),  $linenum, substr($match[0],0,50)));
      }
    }
    return $this;
  }

  public function message(Messenger $messenger) {
    if (!empty($this->alerts)) {
      $details = sprintf( "Found %s files that reference risky function. \n\t-> %s",
        count($this->alerts),
        View::make('table', array( 'headers'=>array('File','Line','Match'),'rows'=>$this->alerts ) )
      );
      $this->score = 1;
      $this->result .= $details;
      $this->action = "You do not need to deactivate these files, but please scrutinize them in the event of a security issue.";
    }
    $messenger->addMessage(get_object_vars($this));
    return $this;
  }
}
