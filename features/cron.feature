Feature: Check crons

  Background:
    Given a WP installation

  Scenario: Cron check is healthy against a normal WordPress install
    When I run `wp launchcheck cron`
    # wp-cli-tests sets DISABLE_WP_CRON=true in wp-config.php by default
    Then STDOUT should contain:
      """
      WP-Cron is disabled.
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

  Scenario: WP Launch Check shows disabled cron message
    When I run `wp launchcheck cron`
    # wp-cli-tests sets DISABLE_WP_CRON=true in wp-config.php by default
    Then STDOUT should contain:
      """
      WP-Cron is disabled.  Pantheon is running `wp cron event run --due-now` once per hour.
      """
