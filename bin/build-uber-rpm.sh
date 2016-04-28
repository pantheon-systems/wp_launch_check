#!/bin/sh
# requires fpm (`gem install fpm`)

name='wp-cli'
version=$(cat VERSION)
iteration="$(date +%Y%m%d%H%M).git$(git rev-parse --short HEAD)"  # datecode + git sha-ref: "201503020102.gitef8e0fb"
arch='x86_64'
url='https://github.com/pantheon-systems/wp-launch-check' # custom plugin for wp-cli
vendor='Pantheon'
description='Custom compiled version of wp-cli for use on Pantheon'
install_prefix='/opt/wp-cli'

fpm -s dir -t rpm  \
    --name "${name}" \
    --version "${version}" \
    --iteration "${iteration}" \
    --architecture "${arch}" \
    --url "${url}" \
    --vendor "${vendor}" \
    --description "${description}" \
    --prefix "$install_prefix" \
    wp-cli.phar
