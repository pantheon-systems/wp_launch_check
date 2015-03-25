#!/bin/bash
WORKINGDIR=$PWD
CLI_DIR=~/wp-cli
sudo git clone https://github.com/wp-cli/wp-cli.git $CLI_DIR
sudo chown -R travis: $CLI_DIR
echo -n "COMPOSER: $(whereis composer)"
if [ -f composer.phar ]
then 
	sudo php ./composer.phar update --working-dir=$CLI_DIR --prefer-dist
else 
	sudo composer update --working-dir=$CLI_DIR --prefer-dist 
fi
sudo rsync --exclude=.git -avzu $WORKINGDIR/php/ $CLI_DIR/php/
cd $CLI_DIR
sudo php -dphar.readonly=0 utils/make-phar.php $WORKINGDIR/wp-cli.phar --quiet
cd $WORKINGDIR
