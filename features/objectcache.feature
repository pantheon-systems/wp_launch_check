Feature: Suggest object cache to be enabled

  Scenario: No object cache is present
    Given a WP install

    When I run `wp launchcheck object-cache`
    Then STDOUT should contain:
      """
      <p class="result">No object-cache.php exists</p>
      """
    And STDOUT should contain:
      """
      <p class="result">Use Object Cache Pro to speed up your backend
      """

  Scenario: An object cache is present but it's not WP Redis
    Given a WP install
    And I run `wp plugin install wp-lcache --activate`
    And I run `wp lcache enable`

    When I run `wp launchcheck object-cache`
    Then STDOUT should contain:
      """
      <p class="result">object-cache.php exists</p>
      """
    And STDOUT should contain:
      """
      <p class="result">Use Object Cache Pro to speed up your backend
      """

  Scenario: WP Redis is present as the enabled object-cache
    Given a WP install
		# TODO Remove the version flag.
    And I run `wp plugin install wp-redis --activate`
    And I run `wp redis enable`

    When I run `wp launchcheck object-cache`
    Then STDOUT should contain:
      """
      <p class="result">object-cache.php exists</p>
      """
    And STDOUT should contain:
      """
      <p class="result">WP Redis for object caching was found. We recommend using Object Cache Pro
      """
