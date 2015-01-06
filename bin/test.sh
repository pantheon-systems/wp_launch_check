#!/bin/bash
git clone git@github.com:wp-cli/wp-cli.git /tmp/wp-cli
composer update -d /tmp/wp-cli/

sudo cp /tmp/wp-cli/bin/wp /usr/local/bin/wp
sudo chmod +x /usr/local/bin/wp
export WP_CLI_ROOT=/tmp/wp-cli
phpunit
