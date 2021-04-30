Feature: Test WordPress for themes with known security issues

  Scenario: A WordPress install with a theme with a known security issue
    Given a WP install
    And I run `wp theme install twentyfifteen --version=1.1 --force`

    When I run `wp launchcheck all --all`
    Then STDOUT should contain:
      """
      Found 0 themes needing updates and 1 known vulnerabilities
      """
    And STDOUT should contain:
      """
      Recommendation: Update themes to fix vulnerabilities
      """

  Scenario: A WordPress install with no theme security issues
    Given a WP install
    And I run `wp plugin update twentyfifteen`

    When I run `wp launchcheck all --all`
    Then STDOUT should contain:
      """
      Found 0 themes needing updates and 0 known vulnerabilities
      """
