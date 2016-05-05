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

  Scenario: Check that $_ENV variables are used to populate database credentials
    Given a WP install
    And a wp-config.php file:
      """
      <?php
      // ** MySQL settings ** //
      /** The name of the database for WordPress */
      define('DB_NAME', $_ENV['DB_NAME'] );

      /** MySQL database username */
      define('DB_USER', $_ENV['DB_USER'] );

      /** MySQL database password */
      define('DB_PASSWORD', $_ENV['DB_PASSWORD'] );

      /** MySQL hostname */
      define('DB_HOST', $_ENV['DB_HOST'] . ':' . $_ENV['DB_PORT'] );

      /** Database Charset to use in creating database tables. */
      define('DB_CHARSET', 'utf8');

      /** The Database Collate type. Don't change this if in doubt. */
      define('DB_COLLATE', '');

      $table_prefix = 'wp_';

      /* That's all, stop editing! Happy blogging. */

      /** Absolute path to the WordPress directory. */
      if ( !defined('ABSPATH') )
        define('ABSPATH', dirname(__FILE__) . '/');

      /** Sets up WordPress vars and included files. */
      require_once(ABSPATH . 'wp-settings.php');
      """
    And a wp-config-env.php file:
      """
      <?php
      $_ENV['PANTHEON_ENVIRONMENT'] = 'dev';
      $_ENV['DB_NAME'] = 'wp_cli_test';
      $_ENV['DB_USER'] = 'wp_cli_test';
      $_ENV['DB_PASSWORD'] = 'password1';
      $_ENV['DB_HOST'] = '127.0.0.1';
      $_ENV['DB_PORT'] = '3306';
      """

    When I run `wp --require=wp-config-env.php launchcheck config`
    Then STDOUT should contain:
      """
      DB_NAME, DB_USER, DB_PASSWORD, DB_HOST are set to their expected $_ENV values.
      """
    And STDOUT should contain:
      """
      Recommendation: No action required
      """

    Given a wp-config.php file:
      """
      <?php
      // ** MySQL settings ** //
      /** The name of the database for WordPress */
      define('DB_NAME', $_ENV['DB_NAME'] );

      /** MySQL database username */
      define('DB_USER', 'baduser' );

      /** MySQL database password */
      define('DB_PASSWORD', 'badpassword' );

      /** MySQL hostname */
      define('DB_HOST', $_ENV['DB_HOST'] . ':' . $_ENV['DB_PORT'] );

      /** Database Charset to use in creating database tables. */
      define('DB_CHARSET', 'utf8');

      /** The Database Collate type. Don't change this if in doubt. */
      define('DB_COLLATE', '');

      $table_prefix = 'wp_';

      /* That's all, stop editing! Happy blogging. */

      /** Absolute path to the WordPress directory. */
      if ( !defined('ABSPATH') )
        define('ABSPATH', dirname(__FILE__) . '/');

      /** Sets up WordPress vars and included files. */
      require_once(ABSPATH . 'wp-settings.php');
      """

    When I run `wp --require=wp-config-env.php launchcheck config`
    Then STDOUT should contain:
      """
      Some database constants differ from their expected $_ENV values: DB_USER, DB_PASSWORD
      """
    And STDOUT should contain:
      """
      Recommendation: Please <a href="https://pantheon.io/docs/wp-config-php/">update your wp-config.php</a> file to support $_ENV-based configuration values.
      """

    When I try `wp --require=wp-config-env.php launchcheck cron`
    Then STDERR should be:
      """
      Error: Error establishing a database connection
      """

    When I try `wp --require=wp-config-env.php launchcheck all`
    Then STDOUT should contain:
      """
      Some database constants differ from their expected $_ENV values: DB_USER, DB_PASSWORD
      """
    And STDOUT should contain:
      """
      Recommendation: Please <a href="https://pantheon.io/docs/wp-config-php/">update your wp-config.php</a> file to support $_ENV-based configuration values.
      """
    And STDERR should contain:
      """
      Warning: Detected invalid database credentials, skipping remaining checks
      """
    And STDERR should not contain:
      """
      Error: Error establishing a database connection
      """
