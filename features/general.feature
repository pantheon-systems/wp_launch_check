Feature: General tests of WP Launch Check

  Scenario: WP Launch Check can be run from a non-ABSPATH directory
    Given a WP install

    When I run `cd wp-content; wp launchcheck cron`
    Then STDOUT should contain:
      """
      CRON: (Checking whether cron is enabled and what jobs are scheduled)
      """

  Scenario: General check warns when domains are mismatched
    Given a WP multisite subdomain install
    And a wp-content/mu-plugins/pantheon-setup.php file:
      """
      <?php
      function pantheon_curl() {
          return array(
              'body' => file_get_contents( dirname( __FILE__ ) . '/sample-data.json' ),
          );
      }
      $_ENV['PANTHEON_ENVIRONMENT'] = 'test';
      """
    And a wp-content/mu-plugins/sample-data.json file:
      """
      {"organization": false, "add_ons": [], "site": {"created": 1446035953, "created_by_user_id": "24980c36-de42-4e59-9b64-1061514fad74", "framework": "wordpress", "holder_id": "24980c36-de42-4e59-9b64-1061514fad74", "holder_type": "user", "last_code_push": {"timestamp": "2015-10-30T20:32:00", "user_uuid": null}, "name": "daniel-pantheon", "owner": "24980c36-de42-4e59-9b64-1061514fad74", "php_version": 55, "preferred_zone": "chios", "service_level": "free", "upstream": {"url": "https://github.com/pantheon-systems/WordPress", "product_id": "e8fe8550-1ab9-4964-8838-2b9abdccf4bf", "branch": "master"}, "label": "daniel-pantheon", "settings": {"allow_domains": false, "max_num_cdes": 10, "environment_styx_scheme": "https", "stunnel": false, "replica_verification_strategy": "legacy", "owner": "24980c36-de42-4e59-9b64-1061514fad74", "secure_runtime_access": false, "pingdom": 0, "allow_indexserver": false, "created_by_user_id": "24980c36-de42-4e59-9b64-1061514fad74", "failover_appserver": 0, "cacheserver": 1, "drush_version": 5, "label": "daniel-pantheon", "appserver": 1, "allow_read_slaves": false, "indexserver": 1, "php_version": 55, "php_channel": "stable", "allow_cacheserver": false, "ssl_enabled": null, "min_backups": 0, "service_level": "free", "dedicated_ip": null, "dbserver": 1, "framework": "wordpress", "upstream": {"url": "https://github.com/pantheon-systems/WordPress", "product_id": "e8fe8550-1ab9-4964-8838-2b9abdccf4bf", "branch": "master"}, "guilty_of_abuse": null, "preferred_zone": "chios", "pingdom_chance": 0, "holder_id": "24980c36-de42-4e59-9b64-1061514fad74", "name": "daniel-pantheon", "created": 1446035953, "max_backups": 0, "holder_type": "user", "number_allow_domains": 0, "pingdom_manually_enabled": false, "last_code_push": {"timestamp": "2015-10-30T20:32:00", "user_uuid": null}}, "base_domain": null}, "environments": {"dev": {"diffstat": {}, "allow_domains": false, "lock": {"username": null, "password": null, "locked": false}, "upstream": {"url": "https://github.com/pantheon-systems/WordPress", "product_id": "e8fe8550-1ab9-4964-8838-2b9abdccf4bf", "branch": "master"}, "environment_styx_scheme": "https", "stunnel": false, "target_ref": "refs/heads/master", "mysql": {"query_cache_size": 32, "innodb_buffer_pool_size": 128, "innodb_log_file_size": 50331648, "BlockIOWeight": 400, "MemoryLimit": 256, "CPUShares": 250}, "owner": "24980c36-de42-4e59-9b64-1061514fad74", "secure_runtime_access": false, "pingdom": 0, "guilty_of_abuse": null, "statuses": {}, "created_by_user_id": "24980c36-de42-4e59-9b64-1061514fad74", "failover_appserver": 0, "errors": {}, "cacheserver": 1, "on_server_development": true, "environment_created": 1446035953, "dns_zone": "pantheon.io", "schedule": {"0": null, "1": null, "2": null, "3": null, "4": null, "5": null, "6": null}, "redis": {"MemoryLimit": 64, "maxmemory": 52428800, "CPUShares": 8, "BlockIOWeight": 50}, "label": "daniel-pantheon", "environment": "dev", "appserver": 1, "number_allow_domains": 0, "allow_read_slaves": false, "indexserver": 1, "php_version": 55, "php_channel": "stable", "allow_cacheserver": false, "ssl_enabled": null, "styx_cluster": "styx-02.pantheon.io", "min_backups": 0, "service_level": "free", "dedicated_ip": null, "dbserver": 1, "site": "73cae74a-b66e-440a-ad3b-4f0679eb5e97", "framework": "wordpress", "holder_id": "24980c36-de42-4e59-9b64-1061514fad74", "max_num_cdes": 10, "allow_indexserver": false, "preferred_zone": "chios", "pingdom_chance": 0, "watchers": 0, "name": "daniel-pantheon", "created": 1446035953, "max_backups": 0, "php-fpm": {"fpm_max_children": 4, "opcache_revalidate_freq": 0, "BlockIOWeight": 100, "MemoryLimit": 512, "apc_shm_size": 128, "php_memory_limit": 256, "CPUShares": 250}, "randseed": "ZGZPLBXZUH63P6U6S6O0E9Q69A48L6GK", "last_code_push": {"timestamp": "2015-10-30T20:32:00", "user_uuid": null}, "loadbalancers": {}, "holder_type": "user", "replica_verification_strategy": "legacy", "urls": ["dev-daniel-pantheon.pantheon.io"], "target_commit": "f83daed591dc5c60425eef57092e6d374575bef5", "pingdom_manually_enabled": false, "nginx": {"sendfile": "off", "aio": "off", "worker_processes": 2, "directio": "off"}, "drush_version": 5}, "live": {"allow_domains": false, "lock": {"username": null, "password": null, "locked": false}, "upstream": {"url": "https://github.com/pantheon-systems/WordPress", "product_id": "e8fe8550-1ab9-4964-8838-2b9abdccf4bf", "branch": "master"}, "environment_styx_scheme": "https", "stunnel": false, "replica_verification_strategy": "legacy", "mysql": {"query_cache_size": 32, "innodb_buffer_pool_size": 128, "innodb_log_file_size": 50331648, "BlockIOWeight": 400, "MemoryLimit": 256, "CPUShares": 250}, "owner": "24980c36-de42-4e59-9b64-1061514fad74", "secure_runtime_access": false, "pingdom": 0, "guilty_of_abuse": null, "statuses": {}, "created_by_user_id": "24980c36-de42-4e59-9b64-1061514fad74", "failover_appserver": 0, "errors": {}, "cacheserver": 1, "loadbalancers": {}, "environment_created": 1446035954, "dns_zone": "pantheon.io", "schedule": {"0": null, "1": null, "2": null, "3": null, "4": null, "5": null, "6": null}, "redis": {"MemoryLimit": 64, "maxmemory": 52428800, "CPUShares": 8, "BlockIOWeight": 50}, "label": "daniel-pantheon", "environment": "live", "appserver": 1, "number_allow_domains": 0, "allow_read_slaves": false, "indexserver": 1, "php_version": 55, "php_channel": "stable", "allow_cacheserver": false, "ssl_enabled": null, "styx_cluster": "styx-01.pantheon.io", "service_level": "free", "dedicated_ip": null, "dbserver": 1, "site": "73cae74a-b66e-440a-ad3b-4f0679eb5e97", "framework": "wordpress", "max_num_cdes": 10, "allow_indexserver": false, "preferred_zone": "chios", "pingdom_chance": 0, "holder_id": "24980c36-de42-4e59-9b64-1061514fad74", "name": "daniel-pantheon", "created": 1446035953, "max_backups": 0, "php-fpm": {"fpm_max_children": 4, "opcache_revalidate_freq": 2, "BlockIOWeight": 100, "MemoryLimit": 512, "apc_shm_size": 128, "php_memory_limit": 256, "CPUShares": 250}, "randseed": "J1Y5E6VJHQ9CGNATQ9ZRW28XEATQVPX4", "last_code_push": {"timestamp": "2015-10-30T20:32:00", "user_uuid": null}, "holder_type": "user", "min_backups": 0, "urls": ["live-daniel-pantheon.pantheon.io"], "pingdom_manually_enabled": false, "nginx": {"sendfile": "off", "aio": "off", "worker_processes": 2, "directio": "off"}, "drush_version": 5}, "test": {"allow_domains": false, "lock": {"username": null, "password": null, "locked": false}, "upstream": {"url": "https://github.com/pantheon-systems/WordPress", "product_id": "e8fe8550-1ab9-4964-8838-2b9abdccf4bf", "branch": "master"}, "environment_styx_scheme": "https", "stunnel": false, "target_ref": "refs/tags/pantheon_test_2", "mysql": {"query_cache_size": 32, "innodb_buffer_pool_size": 128, "innodb_log_file_size": 50331648, "BlockIOWeight": 400, "MemoryLimit": 256, "CPUShares": 250}, "owner": "24980c36-de42-4e59-9b64-1061514fad74", "secure_runtime_access": false, "pingdom": 0, "guilty_of_abuse": null, "statuses": {}, "created_by_user_id": "24980c36-de42-4e59-9b64-1061514fad74", "failover_appserver": 0, "errors": {}, "cacheserver": 1, "loadbalancers": {}, "environment_created": 1446035954, "dns_zone": "pantheon.io", "schedule": {"0": null, "1": null, "2": null, "3": null, "4": null, "5": null, "6": null}, "redis": {"MemoryLimit": 64, "maxmemory": 52428800, "CPUShares": 8, "BlockIOWeight": 50}, "label": "daniel-pantheon", "environment": "test", "appserver": 1, "number_allow_domains": 0, "allow_read_slaves": false, "indexserver": 1, "php_version": 55, "php_channel": "stable", "allow_cacheserver": false, "ssl_enabled": null, "styx_cluster": "styx-03.pantheon.io", "min_backups": 0, "service_level": "free", "dedicated_ip": null, "dbserver": 1, "site": "73cae74a-b66e-440a-ad3b-4f0679eb5e97", "framework": "wordpress", "max_num_cdes": 10, "allow_indexserver": false, "preferred_zone": "chios", "pingdom_chance": 0, "holder_id": "24980c36-de42-4e59-9b64-1061514fad74", "name": "daniel-pantheon", "created": 1446035953, "max_backups": 0, "php-fpm": {"fpm_max_children": 4, "opcache_revalidate_freq": 2, "BlockIOWeight": 100, "MemoryLimit": 512, "apc_shm_size": 128, "php_memory_limit": 256, "CPUShares": 250}, "randseed": "7LU1MNL20EPAJ8XTN0FVF2B94BIKQ354", "last_code_push": {"timestamp": "2015-10-30T20:32:00", "user_uuid": null}, "holder_type": "user", "replica_verification_strategy": "legacy", "urls": ["test-daniel-pantheon.pantheon.io", "subsite.test-daniel-pantheon.pantheon.io"], "target_commit": "f83daed591dc5c60425eef57092e6d374575bef5", "pingdom_manually_enabled": false, "nginx": {"sendfile": "off", "aio": "off", "worker_processes": 2, "directio": "off"}, "drush_version": 5}}, "instrument": null}
      """
    And I run `wp site create --slug=subsite`

    When I run `wp launchcheck general`
    Then STDOUT should contain:
      """
      One or more WordPress domains are not registered as Pantheon domains: example.com, subsite.example.com
      """

    When I run `wp search-replace example.com test-daniel-pantheon.pantheon.io --network`
    Then STDOUT should not be empty

    When I run `wp launchcheck general --url=test-daniel-pantheon.pantheon.io`
    Then STDOUT should contain:
      """
      WordPress domains are verified to be in sync with Pantheon domains.
      """

  Scenario: WordPress is up to date
    Given a WP install

    When I run `wp core version`
    # This check is here to remind us to update versions when new releases are available.
    Then STDOUT should contain:
      """
      6.5
      """

    When I run `wp launchcheck general`
    Then STDOUT should contain:
      """
      WordPress is at the latest version.
      """

  Scenario: WordPress has a new minor version but no new major version
    Given a WP install
    And I run `wp core download --version=6.5 --force`
    And I run `wp theme activate twentytwentytwo`
    And the current WP version is not the latest

    When I run `wp launchcheck general`
    Then STDOUT should contain:
      """
      Updating to WordPress' newest minor version is strongly recommended.
      """

  Scenario: WordPress has a new major version but no new minor version
    Given a WP install
    And I run `wp core download --version=6.4.3 --force`
    And I run `wp theme activate twentytwentytwo`

    When I run `wp launchcheck general`
    Then STDOUT should contain:
      """
      A new major version of WordPress is available for update.
      """
