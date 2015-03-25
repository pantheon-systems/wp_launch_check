#!/bin/bash
whereis composer
if [ -f composer.phar ]; then php ./composer.phar update; else composer update; fi
phpunit

WORKINGDIR=$PWD
CLIDIR=/tmp/wp-cli
sudo git clone https://github.com/wp-cli/wp-cli $CLIDIR
ARGS="--working-dir=$CLIDIR --prefer-dist"
if [ -f composer.phar ]; then php ./composer.phar update $ARGS; else composer update $ARGS; fi
sudo rsync --exclude=.git -avzu $WORKINGDIR/php/ $CLIDIR/php/
cd $CLIDIR
sudo php -dphar.readonly=0 utils/make-phar.php $WORKINGDIR/wp-cli.phar --quiet
cd $WORKINGDIR
