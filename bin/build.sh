#!/bin/bash
WORKINGDIR=$PWD
sudo git clone https://github.com/wp-cli/wp-cli.git /tmp/wp-cli
if [ ! -d /tmp/wp-cli ]; then
	cd /tmp/wp-cli
fi

cd /tmp/wp-cli
sudo composer update
sudo rsync --exclude=.git -avzu $WORKINGDIR/php/ /tmp/wp-cli/php/
sudo php -dphar.readonly=0 utils/make-phar.php wp-cli.phar --quiet
sudo cp wp-cli.phar $WORKINGDIR/

if [[ ! $( type s3cmd ) ]]; then 
	sudo apt-get install s3cmd
fi;

sudo echo "[default]
access_key = $AMAZON_ACCESS_ID
acl_public = False
bucket_location = US
cloudfront_host = cloudfront.amazonaws.com
cloudfront_resource = /2008-06-30/distribution
default_mime_type = binary/octet-stream
delete_removed = False
dry_run = False
encoding = UTF-8
encrypt = False
force = False
get_continue = False
gpg_command = /usr/bin/gpg
gpg_decrypt = %(gpg_command)s -d --verbose --no-use-agent --batch --yes --passphrase-fd %(passphrase_fd)s -o %(output_file)s %(input_file)s
gpg_encrypt = %(gpg_command)s -c --verbose --no-use-agent --batch --yes --passphrase-fd %(passphrase_fd)s -o %(output_file)s %(input_file)s
gpg_passphrase =
guess_mime_type = True
host_base = s3.amazonaws.com
host_bucket = %(bucket)s.s3.amazonaws.com
human_readable_sizes = False
list_md5 = False
preserve_attrs = True
progress_meter = True
proxy_host =
proxy_port = 0
recursive = False
recv_chunk = 4096
secret_key = $AMAZON_SECRET_KEY
send_chunk = 4096
simpledb_host = sdb.amazonaws.com
skip_existing = False
urlencoding_mode = normal
use_https = False
verbosity = WARNING" > ~/.s3cfg

version=$( cat $WORKINGDIR/VERSION )
wp_version=$( cat /tmp/wp-cli/VERSION )
s3cmd put --acl-public wp-cli.phar s3://wp-cli/wp-cli-travis-$wp_version-lc-$version.phar

