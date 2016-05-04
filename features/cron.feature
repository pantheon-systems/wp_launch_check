Feature: Check crons

  Background:
    Given a WP install

  Scenario: WP Launch Check normally outputs a list of crons
    When I run `wp launchcheck cron`
    Then STDOUT should contain:
      """
      wp_version_check
      """

  Scenario: WP Launch Check warns when DISABLE_WP_CRON is defined to be true
    Given a local-config.php file:
      """
      <?php
      define( 'DISABLE_WP_CRON', true );
      """

    When I run `wp --require=local-config.php launchcheck cron`
    Then STDOUT should contain:
      """
      Cron appears to be disabled, make sure DISABLE_WP_CRON is not defined in your wp-config.php
      """

    When I run `wp launchcheck cron`
    Then STDOUT should contain:
      """
      Cron is enabled.
      """
