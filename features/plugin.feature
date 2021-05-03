Feature: Test WordPress for plugins with known security issues

  Scenario: A WordPress install with a plugin with a known security issue
    Given a WP install
    And I run `wp plugin install akismet --version=3.1.3 --force`
	And I run `wp plugin update --dry-run`

    When I run `wp launchcheck all`
    Then STDOUT should contain:
      """
      Found 0 plugins needing updates and 1 known vulnerabilities
      """
    And STDOUT should contain:
      """
      Recommendation: Update plugins to fix vulnerabilities
      """

  Scenario: A WordPress install with no plugin security issues
    Given a WP install
    And I run `wp plugin update akismet`

    When I run `wp launchcheck all`
    Then STDOUT should contain:
      """
      Found 0 plugins needing updates and 0 known vulnerabilities
      """
