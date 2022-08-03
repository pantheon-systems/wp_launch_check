Feature: Test WordPress for themes with known security issues

  Scenario: A WordPress install with a theme with a known security issue
    Given a WP install
    And I run `wp theme install twentyfifteen --version=1.1 --force`
		And I run `wp theme update twentyfifteen --dry-run`

    When I run `wp launchcheck all --all`
    Then STDOUT should contain:
      """
      Found 1 themes needing updates and 0 known vulnerabilities
      """
    And STDOUT should contain:
      """
      Recommendation: You should update all out-of-date themes
      """

  Scenario: A WordPress install with no theme security issues
    Given a WP install
		And I run `wp theme install twentyfifteen --version=1.1 --force`
    And I run `wp theme update twentyfifteen`

    When I run `wp launchcheck all --all`
    Then STDOUT should contain:
      """
      Found 0 themes needing updates and 0 known vulnerabilities
      """
