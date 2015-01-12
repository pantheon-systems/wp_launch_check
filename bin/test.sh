#!/bin/bash
whereis composer
if [ -f composer.phar ]; then php ./composer.phar update; else composer update; fi
phpunit
