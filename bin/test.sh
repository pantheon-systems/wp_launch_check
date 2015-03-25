#!/bin/bash
whereis composer
if [ -f composer.phar ]; then php ./composer.phar update; else composer update; fi
phpunit

WORKINGDIR=$PWD
CLIDIR=/home/travis/wp-cli
sudo git clone https://github.com/wp-cli/wp-cli wp-cli
ARGS="--working-dir=$CLI_DIR --prefer-dist"
if [ -f composer.phar ]; then php ./composer.phar update $ARGS; else composer update $ARGS; fi
sudo rsync --exclude=.git -avzu $WORKINGDIR/php/ $CLI_DIR/php/
cd $CLI_DIR
sudo php -dphar.readonly=0 utils/make-phar.php $WORKINGDIR/wp-cli.phar --quiet
cd $WORKINGDIR

