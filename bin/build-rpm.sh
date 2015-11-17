#!/bin/sh
# requires fpm (`gem install fpm`)

name='wp-launch-check'
version=$(cat VERSION)
iteration="$(date +%Y%m%d%H%M).git$(git rev-parse --short HEAD)"  # datecode + git sha-ref: "201503020102.gitef8e0fb"
arch='x86_64'
url='https://github.com/pantheon-systems/wp_launch_check'
vendor='Pantheon'
description='Distribution version of wp-launch-check'
install_prefix='/opt/wp-launch-check'

fpm -s dir -t rpm  \
    --name "${name}" \
    --version "${version}" \
    --iteration "${iteration}" \
    --architecture "${arch}" \
    --url "${url}" \
    --vendor "${vendor}" \
    --description "${description}" \
    --prefix "$install_prefix" \
    wp-launch-check.phar
