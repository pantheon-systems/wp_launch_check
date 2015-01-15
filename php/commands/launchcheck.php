<?php
/**
* Implements example command.
* @version alpha-0.1
*/
class LaunchCheck extends WP_CLI_Command {
  public $fs;
  public $skipfiles = array();
  public $output = array();

  /**
  * run all checks
  *
  * ## OPTIONS
  *
  */
  public function all($args, $assoc_args) {
    $searcher = new \Pantheon\Filesearcher(\WP_CLI::get_config("path").'/wp-content');
    $searcher->register( new \Pantheon\Checks\Sessions() );
    $searcher->register( new \Pantheon\Checks\Insecure() );
    $searcher->register( new \Pantheon\Checks\Exploited() );
    $searcher->execute();
    $checker = new \Pantheon\Checker();
    $checker->register( new \Pantheon\Checks\Plugins( isset($assoc_args['all'])) );
    $checker->register( new \Pantheon\Checks\Objectcache() );
    $checker->execute();
    $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
    \Pantheon\Messenger::emit($format);
  }

  /**
  * checks for object caching
  *
  * ## OPTIONS
  *
  * [--format=<format>]
  * : output as json
  *
  * ## EXAMPLES
  *
  *   wp launchcheck object-cache
  *
  * @alias object-cache
  */
  public function object_cache($args, $assoc_args) {
    $checker = new \Pantheon\Checker();
    $checker->register( new \Pantheon\Checks\Objectcache() );
    $checker->execute();
    $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
    \Pantheon\Messenger::emit($format);
  }

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
    $searcher = new \Pantheon\Filesearcher(\WP_CLI::get_config("path").'/wp-content');
    $searcher->register( new \Pantheon\Checks\Insecure() );
    $searcher->register( new \Pantheon\Checks\Exploited() );
    $searcher->execute();
    $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
    \Pantheon\Messenger::emit($format);
  }

  /**
  * checks plugins for vulnerbities using the wpscan vulnerability DB
  * - https://wpvulndb.com/api
  *
  * ## OPTIONS
  *
  * [--all]
  * : check both active and inactive plugins ( default is active only )
  *
  * [--format=<format>]
  * : output as json
  *
  * ## EXAMPLES
  *
  *   wp launchcheck plugins --all
  *
  */
  public function plugins($args, $assoc_args) {
    $checker = new \Pantheon\Checker();
    $checker->register( new \Pantheon\Checks\Plugins( isset($assoc_args['all'])) );
    $checker->execute();
    $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
    \Pantheon\Messenger::emit($format);
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
    $sessions = new \Pantheon\Checks\Sessions(\WP_CLI::get_config("path").'/wp-content');
    $message = $sessions->init()->run();
    \Pantheon\Messenger::queue($message);
    $this->handle_output($assoc_args);
  }

  private function handle_output($json=false) {

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
