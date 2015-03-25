#!/bin/bash
WORKINGDIR=$PWD
CLI_DIR=~/wp-cli
sudo git clone https://github.com/wp-cli/wp-cli.git $CLI_DIR
sudo composer update --working-dir=$CLI_DIR
sudo rsync --exclude=.git -avzu $WORKINGDIR/php/ $CLI_DIR/php/
cd $CLI_DIR
sudo php -dphar.readonly=0 utils/make-phar.php $WORKINGDIR/wp-cli.phar --quiet
cd $WORKINGDIR
