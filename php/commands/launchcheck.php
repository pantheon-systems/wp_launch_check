<?php
/**
 * Implements example command.
 * @version 0.1.4
 */
class LaunchCheck extends WP_CLI_Command {

	/**
	 * run all checks
	 *
	 * ## OPTIONS
	 *
	 * [--format=<json>]
	 * : use to output json
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function all( $args, $assoc_args ) {
		unset( $args );
		$searcher = new \Pantheon\Filesearcher( getcwd() . '/wp-content' );
		$searcher->register( new \Pantheon\Checks\Sessions() );
		$searcher->register( new \Pantheon\Checks\Insecure() );
		$searcher->register( new \Pantheon\Checks\Exploited() );
		$searcher->execute();
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\Plugins( isset( $assoc_args['all'] ) ) );
		$checker->register( new \Pantheon\Checks\Cron() );
		$checker->register( new \Pantheon\Checks\Objectcache() );
		$checker->register( new \Pantheon\Checks\Database() );
		$checker->execute();
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit( $format );
	}

	/**
	 * Checks the cron
	 *
	 * ## OPTIONS
	 *
	 * [--format=<json>]
	 * : use to output json
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	function cron( $args, $assoc_args ) {
		unset( $args );
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\Cron() );
		$checker->execute();
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit( $format );
	}

	/**
	 * Check database for potential issues
	 *
	 * ## OPTIONS
	 *
	 * [--format=<json>]
	 * : use to output json
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	function database( $args, $assoc_args ) {
		unset( $args );
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\Database() );
		$checker->execute();
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit( $format );
	}

	/**
	 * Checks for best practice
	 *
	 * ## OPTIONS
	 *
	 * [--format=<json>]
	 * : use to output json
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	function general( $args, $assoc_args ) {
		unset( $args );
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\General() );
		$checker->execute();
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit( $format );
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
	 *     wp launchcheck object-cache
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @alias object-cache
	 */
	public function object_cache( $args, $assoc_args ) {
		unset( $args );
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\Objectcache() );
		$checker->execute();
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit( $format );
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
	 *     wp secure --skip=wp-content/themes
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function secure( $args, $assoc_args ) {
		unset( $args );
		$searcher = new \Pantheon\Filesearcher( getcwd() . '/wp-content' );
		$searcher->register( new \Pantheon\Checks\Insecure() );
		$searcher->register( new \Pantheon\Checks\Exploited() );
		$searcher->execute();
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit( $format );
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
	 *     wp launchcheck plugins --all
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function plugins( $args, $assoc_args ) {
		unset( $args );
		$checker = new \Pantheon\Checker();
		$checker->register( new \Pantheon\Checks\Plugins( isset( $assoc_args['all'] ) ) );
		$checker->execute();
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit( $format );
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
	 *     wp launchcheck sessions
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function sessions( $args, $assoc_args ) {
		unset( $args );
		$searcher = new \Pantheon\Filesearcher( getcwd().'/wp-content' );
		$searcher->register( new \Pantheon\Checks\Sessions() );
		$searcher->execute();
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'raw';
		\Pantheon\Messenger::emit( $format );
	}
}

// register our autoloader
spl_autoload_register( function ( $class ) {
	if ( class_exists( $class ) ) { return $class; }
	$class = strtolower( $class );
	if ( strstr( $class, 'pantheon' ) ) {
		$class = str_replace( '\\', '/', $class );
		$path = WP_CLI_ROOT . '/php/' . $class . '.php';
		if ( file_exists( $path ) ) {
			require_once( $path );
		}
	}
} );

WP_CLI::add_command( 'launchcheck', 'LaunchCheck' );
