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
      <p class="result">Use Redis with the WP Redis object cache drop-in
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
      <p class="result">Use Redis with the WP Redis object cache drop-in
      """

  Scenario: WP Redis is present as the enabled object-cache
    Given a WP install
    And I run `wp plugin install wp-redis --version=1.3.5 --activate`
    And I run `wp redis enable`

    When I run `wp launchcheck object-cache`
    Then STDOUT should contain:
      """
      <p class="result">object-cache.php exists</p>
      """
    And STDOUT should contain:
      """
      <p class="result">Redis found
      """
