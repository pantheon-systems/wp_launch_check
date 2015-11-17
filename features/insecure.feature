Feature: Test WordPress for insecure files

  Scenario: A WordPress install with an insecure file
    Given a WP install
    And a wp-content/mu-plugins/insecure-file.php file:
      """
      <?php
      eval('\<\? echo "tem";');
      """

    When I run `wp launchcheck all`
    Then STDOUT should contain:
      """
      Recommendation: You do not need to deactivate these files, but please scrutinize them in the event of a security issue.
      """

  Scenario: A WordPress install with an insecure file
    Given a WP install
    And a wp-content/mu-plugins/insecure-file.php file:
      """
      <?php
      $security = 'obscurity';
      """

    When I run `wp launchcheck all`
    Then STDOUT should contain:
      """
      Recommendation: We did not find any files running risky functions.
      """
