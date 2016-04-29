Feature: Check the wp-config.php file

	Scenario: WP Launch Check warns when WP_CACHE is defined to be true
		Given a WP install
		And a local-config.php file:
			"""
			<?php
			define( 'WP_CACHE', true );
			"""

		When I run `wp --require=local-config.php launchcheck config`
		Then STDOUT should contain:
			"""
			The WP_CACHE constant is set to true, and should be removed
			"""

		When I run `wp launchcheck config`
		Then STDOUT should contain:
			"""
			WP_CACHE not found or is set to false.
			"""

	Scenario: Check that $_SERVER['SERVER_NAME'] isn't being used to define WP_HOME or WP_SITEURL
		Given a WP install

		When I run `wp launchcheck config`
		Then STDOUT should contain:
			"""
			Verified that $_SERVER['SERVER_NAME'] isn't being used to define WP_HOME or WP_SITE_URL
			"""

		Given a wp-config.php file:
			"""
			<?php
			// ** MySQL settings ** //
			/** The name of the database for WordPress */
			define('DB_NAME', 'wp_cli_test');

			/** MySQL database username */
			define('DB_USER', 'wp_cli_test');

			/** MySQL database password */
			define('DB_PASSWORD', 'password1');

			/** MySQL hostname */
			define('DB_HOST', '127.0.0.1');

			/** Database Charset to use in creating database tables. */
			define('DB_CHARSET', 'utf8');

			/** The Database Collate type. Don't change this if in doubt. */
			define('DB_COLLATE', '');

			$table_prefix = 'wp_';

			define( 'WP_SITEURL', $_SERVER['SERVER_NAME'] );

			/* That's all, stop editing! Happy blogging. */

			/** Absolute path to the WordPress directory. */
			if ( !defined('ABSPATH') )
				define('ABSPATH', dirname(__FILE__) . '/');

			/** Sets up WordPress vars and included files. */
			require_once(ABSPATH . 'wp-settings.php');
			"""

		When I run `wp launchcheck config`
		Then STDOUT should contain:
			"""
			$_SERVER['SERVER_NAME'] appears to be used to define WP_HOME or WP_SITE_URL, which will be unreliable on Pantheon.
			"""

		Given a wp-config.php file:
			"""
			<?php
			// ** MySQL settings ** //
			/** The name of the database for WordPress */
			define('DB_NAME', 'wp_cli_test');

			/** MySQL database username */
			define('DB_USER', 'wp_cli_test');

			/** MySQL database password */
			define('DB_PASSWORD', 'password1');

			/** MySQL hostname */
			define('DB_HOST', '127.0.0.1');

			/** Database Charset to use in creating database tables. */
			define('DB_CHARSET', 'utf8');

			/** The Database Collate type. Don't change this if in doubt. */
			define('DB_COLLATE', '');

			$table_prefix = 'wp_';

			define( 'WP_SITEURL', $_SERVER['HTTP_HOST'] );

			/* That's all, stop editing! Happy blogging. */

			/** Absolute path to the WordPress directory. */
			if ( !defined('ABSPATH') )
				define('ABSPATH', dirname(__FILE__) . '/');

			/** Sets up WordPress vars and included files. */
			require_once(ABSPATH . 'wp-settings.php');
			"""

		When I run `wp launchcheck config`
		Then STDOUT should contain:
			"""
			Verified that $_SERVER['SERVER_NAME'] isn't being used to define WP_HOME or WP_SITE_URL
			"""
