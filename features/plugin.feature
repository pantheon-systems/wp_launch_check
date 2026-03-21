Feature: Test WordPress for plugins with known security issues

  Scenario: A WordPress install with no plugin security issues
    Given a WP install

    When I run `wp launchcheck all`
    Then STDOUT should contain:
      """
      Found 0 plugins needing updates
      """
