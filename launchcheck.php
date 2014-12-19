<?php
/**
* Implements example command.
*/
use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Finder\Finder;

class LaunchCheck extends WP_CLI_Command {
	public $fs;
	public $skipfiles = array();
	public $output = array();

	/**
	* Searches php files for the provided regex
	* 
	* @param $dir string directory to start from
	* @param $regex string undelimited pattern to match
	*
	* @return array an array of matched files or empty if none found
	**/
	private function search_php_files($dir, $regex) {
		$fs = $this->load_fs();
    $finder = new Finder();

    // find all files ending in PHP
    $files = $finder->files()->in($dir)->name("*.php");
    $alerts = array();	
		
		foreach ( $files as $file ) {
			if ( $file->getRelativePathname() !== 'wp-content/plugins/twigify-master/twigify-master.php' ) { continue; }
			if ( WP_CLI::get_config('debug') ) {
				WP_CLI::line( sprintf("-> %s",$file->getRelativePathname()) ); 
			}
	
			if ( preg_match('#'.$regex.'#s',$file->getContents()) ) {
				$alerts[] = $file->getPath().'/'.$file->getFilename();
			}
		}
		return $alerts;

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
  * 	wp launchcheck sessions
  *
  */
  public function sessions( $args, $assoc_args ) {

		// initialize the json output
		$this->output[__METHOD__] = array( 
			'action' => 'You should install the Native PHP Sessions plugin - https://wordpress.org/plugins/wp-native-php-sessions/',
			'description' => "Sessions will only work in the Native PHP Sessions plugin is enabled",
			'score' => 2,
			'result' => '',
			'label'	=> 'PHP Sessions',
		);

		$alerts = $this->search_php_files( WP_CLI::get_config('path'), ".*(session_start|SESSION).*" );
		if (!empty($alerts)) {
			$details = sprintf( "Found %s files that references sessions \n    -> %s", 
							count($alerts), 
							join("\n", array_filter( $alerts, function( $a ) { return str_replace(ABSPATH, '', $a); }) ) 
			);
			$this->output[__METHOD__]['score'] = -1;
			$this->output[__METHOD__]['result'] .= $details;
		}	else {
			$details = 'No files found referencing sessions.';
			$this->output[__METHOD__]['result'] .= $details;
		}
	
    // print a success message
		$this->handle_output( __METHOD__, $assoc_args );	
  }

	private function handle_output( $method, $assoc_args) {
		$output = $this->output[ $method ];
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

	private function load_fs() {
		if ( $this->fs ) {
			return $this->fs;
		}

		$this->fs = new filesystem();
		return $this->fs;	
	}

}

WP_CLI::add_command( 'launchcheck', 'LaunchCheck' );

