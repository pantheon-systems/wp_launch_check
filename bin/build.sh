#!/bin/bash
WORKINGDIR=$PWD
sudo git clone https://github.com/wp-cli/wp-cli.git /tmp/wp-cli
if [ ! -d /tmp/wp-cli ]; then
	cd /tmp/wp-cli
fi

cd /tmp/wp-cli
sudo composer update
sudo rsync --exclude=.git -avzu $WORKINGDIR/php/ /tmp/wp-cli/php/
sudo php -dphar.readonly=0 utils/make-phar.php $WORKINGDIR/wp-cli.phar --quiet
cd $WORKINGDIR
