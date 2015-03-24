#!/bin/bash
WORKINGDIR=$PWD
CLI_DIR=$WORKINGDIR/wp-cli
sudo git clone https://github.com/wp-cli/wp-cli.git $CLI_DIR

cd $CLI_DIR
sudo curl -sS https://getcomposer.org/installer | php
sudo chmod +x composer.phar
sudo php composer.phar update
sudo rsync --exclude=.git -avzu $WORKINGDIR/php/ $CLI_DIR/php/
sudo php -dphar.readonly=0 utils/make-phar.php $WORKINGDIR/wp-cli.phar --quiet
cd $WORKINGDIR
