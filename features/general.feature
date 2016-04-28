Feature: General tests of WP Launch Check

  Scenario: WP Launch Check can be run from a non-ABSPATH directory
    Given a WP install

    When I run `cd wp-content; wp launchcheck cron`
    Then STDOUT should contain:
      """
      CRON: (Checking whether cron is enabled and what jobs are scheduled)
      """
