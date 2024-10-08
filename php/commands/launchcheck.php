<?php
/**
* Performs performance and security checks for WordPress.
* @version 0.6.8
*/
class LaunchCheck {
	public $fs;
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
		$config_check = new \Pantheon\Checks\Config();
		$checker->register( $config_check );
		$checker->execute();

		if ( ! $config_check->valid_db ) {
			WP_CLI::warning( 'Detected invalid database credentials, skipping remaining checks' );
			$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
			\Pantheon\Messenger::emit($format);
			return;
		}

		// wp-config is going to be loaded again, and we need to avoid notices
		@WP_CLI::get_runner()->load_wordpress();
		WP_CLI::add_hook( 'before_run_command', [ $this, 'maybe_switch_to_blog' ] );

		// WordPress is now loaded, so other checks can run
		$searcher = new \Pantheon\Filesearcher( WP_CONTENT_DIR );
		$searcher->register( new \Pantheon\Checks\Sessions() );
		$searcher->execute();
		$checker->register( new \Pantheon\Checks\Plugins(TRUE));
		$checker->register( new \Pantheon\Checks\Themes(TRUE));
		$checker->register( new \Pantheon\Checks\Cron() );
		$checker->register( new \Pantheon\Checks\Objectcache() );
		$checker->register( new \Pantheon\Checks\Database() );
		$checker->execute();
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit($format);
	}

	/**
	 * Switch to BLOG_ID_CURRENT_SITE if we're on a multisite.
	 *
	 * This forces the launchcheck command to use the main site's info for all
	 * the checks.
	 */
	public function maybe_switch_to_blog() {
		// Check for multisite. If we're on multisite, switch to the main site.
		if ( is_multisite() ) {
			if ( defined( 'BLOG_ID_CURRENT_SITE' ) ) {
				switch_to_blog( BLOG_ID_CURRENT_SITE );
			} else {
				switch_to_blog( 1 );
			}
			\WP_CLI::log( sprintf( esc_html__( 'Multisite detected. Running checks on %s site.' ), get_bloginfo( 'name' ) ) );
		}
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
		$checker->execute();
		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit($format);
	}

	/**
	 * Checks plugins for available updates
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
	 * Checks themes for available updates
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : check both active and inactive themes ( default is active only )
	 *
	 * [--format=<format>]
	 * : output as json
	 *
	 * ## EXAMPLES
	 *
	 *   wp launchcheck themes --all
	 *
	 */
	public function themes($args, $assoc_args) {
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\Themes( isset($assoc_args['all']) ) );
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
