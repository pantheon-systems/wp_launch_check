<?php
/**
* Implements example command.
* @version 0.1.4
*/
class LaunchCheck {
	public $fs;
	public $skipfiles = array();
	public $output = array();

	/**
	 * run all checks
	 *
	 * ## OPTIONS
	 *
	 * @when before_wp_load
	 */
	public function all($args, $assoc_args) {
		// Runs before WordPress loads
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\Config() );
		$checker->execute();

		// WordPress is now loaded, so other checks can run
		$searcher = new \Pantheon\Filesearcher( WP_CONTENT_DIR );
		$searcher->register( new \Pantheon\Checks\Sessions() );
		$searcher->register( new \Pantheon\Checks\Insecure() );
		$searcher->register( new \Pantheon\Checks\Exploited() );
		$searcher->execute();
		$checker->register( new \Pantheon\Checks\Plugins(isset($assoc_args['all'])) );
		$checker->register( new \Pantheon\Checks\Cron() );
		$checker->register( new \Pantheon\Checks\Objectcache() );
		$checker->register( new \Pantheon\Checks\Database() );
		$checker->execute();
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit($format);
	}

	/**
	 * Checks for a properly-configured wp-config
	 * 
	 * ## OPTIONS
	 * 
	 * [--format=<json>] 
	 * : use to output json
	 *
	 * @when before_wp_load
	 */
	function config($args, $assoc_args) {
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\Config() );
		$checker->execute();
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit($format);
	}

	/**
	 * Checks the cron
	 * 
	 * ## OPTIONS
	 * 
	 * [--format=<json>] 
	 * : use to output json
	 * 
	 */
	function cron($args, $assoc_args) {
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\Cron() );
		$checker->execute();
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit($format);
	}
	
	/**
	 * Check database for potential issues
	 * 
	 * ## OPTIONS
	 * 
	 * [--format=<json>] 
	 * : use to output json
	 * 
	 */
	function database($args, $assoc_args) {
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\Database() );
		$checker->execute();
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit($format);
	}

	/**
	 * Checks for best practice
	 * 
	 * ## OPTIONS
	 * 
	 * [--format=<json>] 
	 * : use to output json
	 * 
	 */
	function general($args, $assoc_args) {
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\General() );
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
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
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
		$searcher = new \Pantheon\Filesearcher( WP_CONTENT_DIR );
		$searcher->register( new \Pantheon\Checks\Insecure() );
		$searcher->register( new \Pantheon\Checks\Exploited() );
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
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
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
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
		$searcher = new \Pantheon\Filesearcher( WP_CONTENT_DIR );
		$searcher->register( new \Pantheon\Checks\Sessions() );
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
		$searcher->execute();
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit($format);
	}
}

// register our autoloader
spl_autoload_register(function($class) {
	if (class_exists($class)) return $class;
	$class = strtolower($class);
	if (strstr($class,"pantheon")) {
		$class = str_replace('\\','/',$class);
		$path = dirname( dirname( __FILE__ ) ) ."/".$class.'.php';
		if (file_exists($path)) {
			require_once($path);
		}
	}
});

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'launchcheck', 'LaunchCheck' );
}
