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
