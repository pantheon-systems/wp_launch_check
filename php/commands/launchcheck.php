<?php
/**
* Implements example command.
*/
class LaunchCheck extends WP_CLI_Command {
  public $fs;
  public $skipfiles = array();
  public $output = array();

  /**
  * checks files for insecure code and checks the wpvulndb.com/api for known vulnerabilities
  *
  * ## OPTIONS
  *
  * [--skip=<regex>]
  * : a regular expression matching directories to skip
  *
  * [--format=<format>]
  * : output as json
  *
  * ## EXAMPLES
  *
  *   wp secure --skip=wp-content/themes
  *
  */
  public function secure($args, $assoc_args) {
    $insecure = new \Pantheon\Checks\Insecure();
    $message = $insecure->init()->run();
    \Pantheon\Messenger::queue($message);

    $exploited = new \Pantheon\Checks\Exploited();
    $message = $exploited->init()->run();
    \Pantheon\Messenger::queue($message);

    $this->handle_output($assoc_args);
  }

  /**
  * checks the files for session_start()
  *
  * ## OPTIONS
  *
  * [--format=<format>]
  * : output as json
  *
  * ## EXAMPLES
  *
  *   wp launchcheck sessions
  *
  */
  public function sessions( $args, $assoc_args ) {
    $alerts = array();

    // initialize the json output
    $message = array(
      'action' => 'You should install the Native PHP Sessions plugin - https://wordpress.org/plugins/wp-native-php-sessions/',
      'description' => "Sessions will only work in the Native PHP Sessions plugin is enabled",
      'score' => 2,
      'result' => '',
      'label' => 'PHP Sessions',
    );

    $search_path = rtrim(WP_CLI::get_config('path'),'/').'/wp-content/';
    $has_plugin = class_exists('Pantheon_Sessions');

    if ( !$has_plugin ) {
      $alerts = \Pantheon\Utils::search_php_files( $search_path, ".*(session_start|SESSION).*" );
    }

    if (!empty($alerts)) {
      $details = sprintf( "Found %s files that reference sessions \n\t-> %s",
              count($alerts),
              join("\n\t-> ", $alerts )
      );
      $this->output[__METHOD__]['score'] = -1;
      $this->output[__METHOD__]['result'] .= $details;
    } else {
      if ( $has_plugin ) {
        $details = 'You are running wp-native-php-sessions plugin. No scan needed';
      } else {
        $details = 'No files found referencing sessions.';
      }
      $this->output[__METHOD__]['result'] .= $details;
    }

    // print a success message
    $this->handle_output( __METHOD__, $assoc_args );
  }

  private function handle_output($json=false) {
    \Pantheon\Messenger::emit();
    exit;
  }

  /**
   * adds a message to the output array
   */
  private function add_message($message) {
    $this->ouput = array_push($this->output, $message);
  }

}

// register our autoloader
spl_autoload_register(function($class) {
  if (class_exists($class)) return $class;
  $class = strtolower($class);
  if (strstr($class,"pantheon")) {
    $class = str_replace('\\','/',$class);
    $path = WP_CLI_ROOT."/php/".$class.'.php';
    if (file_exists($path)) {
      require_once($path);
    }
  }
});

WP_CLI::add_command( 'launchcheck', 'LaunchCheck' );
