<?php
/**
* Implements example command.
*/
require_once __DIR__.'/../pantheon/utils.php';

class LaunchCheck extends WP_CLI_Command {
  public $fs;
  public $skipfiles = array();
  public $output = array();

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
    $this->output[__METHOD__] = array( 
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
      $details = sprintf( "Found %s files that references sessions \n\t-> %s", 
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

  private function handle_output( $method, $assoc_args) {
    $output = $this->output;
    if ( $method == 'all' ) {
      $output = $this->output;
    }
    if ( isset($assoc_args['format']) AND $assoc_args['format'] === 'json' ) {
      WP_CLI::print_value( $output , $assoc_args );
    } else {
      foreach( $output as $func => $data ) {
        if ( $data['score'] == 2 ) {
          $color = "%G";
        } elseif ( $data['score'] == 0 ) {
          $color = "%C";
        } else {
          $color = "%R";
        }
        echo \cli\Colors::colorize( sprintf(PHP_EOL."%s: (%s) \n Result:%s %s\n Recommendation: %s".PHP_EOL, 
              strtoupper($data['label']),
              $data['description'], 
              $color, 
              $data['result'].'%n', // ugly 
              $data['action']) 
            );
      }
    }
    return $output;
    exit;
  }

}

WP_CLI::add_command( 'launchcheck', 'LaunchCheck' );

