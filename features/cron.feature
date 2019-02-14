Feature: Check crons

  Background:
    Given a WP install

  Scenario: Cron check is healthy against a normal WordPress install
    When I run `wp launchcheck cron`
    Then STDOUT should contain:
      """
      Cron is enabled.
      """
    And STDOUT should not contain:
      """
      cron job(s) with an invalid time.
      """
    And STDOUT should not contain:
      """
      Some jobs are registered more than 10 times, which is excessive and may indicate a problem with your code.
      """
    And STDOUT should not contain:
      """
      This is too many to display and may indicate a problem with your site.
      """

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

  Scenario: Cron check warns when there are too many crons to display
    Given a wp-content/mu-plugins/plugin.php file:
      """
      <?php
      for ( $i=0; $i < 11; $i++ ) {
          // WP Cron doesn't permit registering two at the same time
          // so we need to distribute these crons against a spread of time
          wp_schedule_event( time() + ( $i * 3 ), 'hourly', 'too_many_crons_hook' );
      }
      """

    When I run `wp launchcheck cron`
    Then STDOUT should contain:
      """
      Some jobs are registered more than 10 times, which is excessive and may indicate a problem with your code. These jobs include: too_many_crons_hook
      """
    And STDOUT should contain:
      """
      This is too many to display and may indicate a problem with your site.
      """
