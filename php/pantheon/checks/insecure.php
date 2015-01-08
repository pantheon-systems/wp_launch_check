<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;

class Insecure extends Checkimplementation {

  public function init() {
    $this->action = 'We did not find any files running risky functions.';
    $this->description = 'PHP files running eval or base64_decode on user input can be insecure.';
    $this->score = 2;
    $this->result = '';
    $this->label = 'Risky PHP Functions';
    return $this;
  }

  public function run() {
    $regex = '.*(eval|base64_decode)\(.*';
    $search_path = \WP_CLI::get_config('path').'/wp-content/';
    $alerts = \Pantheon\Utils::search_php_files($search_path, $regex);

    if (!empty($alerts)) {
      $details = sprintf( "Found %s files that reference risky function. \n\t-> %s",
      count($alerts),
      join("\n\t-> ", $alerts)
      );
      $this->score = 0;
      $this->result .= $details;
      $this->recommendation = "You do not need to deactivate these files, but please scrutinize them in the event of a security issue.";
    }

    return $this;
  }
}
