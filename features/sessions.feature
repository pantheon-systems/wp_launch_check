Feature: Test for the existence of the PHP Native Sessions plugin

  Scenario: A WordPress install without the native sessions plugin
    Given a WP install

    When I run `wp launchcheck sessions`
    Then STDOUT should contain:
      """
      You should install the Native PHP Sessions plugin
      """

  Scenario: A WordPress install with the native sessions plugin installed but not active
    Given a WP install
	And I run `wp plugin install wp-native-php-sessions`

	When I run `wp launchcheck sessions`
	Then STDOUT should contain:
	  """
	  You should isntall the Native PHP Sessions plugin
	  """

  Scenario: A WordPress install with the native sessions plugin installed and active
	Given a WP install
	And I run `wp plugin install wp-native-php-sessions`
	And I run `wp plugin activate wp-native-php-sessions`

	When I run `wp launchcheck sessions`
	Then STDOUT should contain:
	  """
	  No action required
	  """
