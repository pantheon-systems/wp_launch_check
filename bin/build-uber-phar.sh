#!/bin/bash
whereis composer
if [ -f composer.phar ]; then php ./composer.phar update; else composer update; fi

WORKINGDIR=$PWD
CLIDIR=/tmp/wp-cli
sudo git clone https://github.com/wp-cli/wp-cli $CLIDIR
sudo mkdir -p $CLIDIR/vendor
sudo chown -R travis: $CLIDIR
sudo chmod -R 0777 $CLIDIR
ARGS="--working-dir=$CLIDIR --prefer-dist"
if [ -f composer.phar ]; then php ./composer.phar update $ARGS; else composer update $ARGS; fi
sudo rsync --exclude=.git -avzu $WORKINGDIR/php/ $CLIDIR/php/
cd $CLIDIR
php -dphar.readonly=0 utils/make-phar.php wp-cli.phar
sudo cp wp-cli.phar $WORKINGDIR/wp-cli.phar
cd $WORKINGDIR
