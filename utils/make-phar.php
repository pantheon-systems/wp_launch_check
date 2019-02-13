<?php

define( 'WP_LAUNCH_CHECK_ROOT', dirname( dirname( __FILE__ ) ) );

require WP_LAUNCH_CHECK_ROOT . '/vendor/autoload.php';

use Symfony\Component\Finder\Finder;

// Alias -- how to refer to paths inside the phar
$alias = 'wp_launch_check.phar';

// DEST_PATH -- where to write the actual phar on the filesystem
define( 'DEST_PATH', $alias );

function add_file( $phar, $path ) {
	$key = str_replace( WP_LAUNCH_CHECK_ROOT, '', $path );
	echo "$key - $path\n";
	$phar[ $key ] = file_get_contents( $path );
}

function set_file_contents( $phar, $path, $content ) {
	$key = str_replace( WP_LAUNCH_CHECK_ROOT, '', $path );
	echo "$key - $path\n";
	$phar[ $key ] = $content;
}

$phar = new Phar( DEST_PATH, 0, $alias );

$phar->startBuffering();

// PHP files
$finder = new Finder();
$finder
	->files()
	->ignoreVCS(true)
	->name('*.php')
	->in(WP_LAUNCH_CHECK_ROOT . '/php')
	->exclude('test')
	->exclude('tests')
	->exclude('Tests')
	;

foreach ( $finder as $file ) {
	add_file( $phar, $file );
}

$phar->setStub( <<<EOB
<?php
Phar::mapPhar();
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "Error: wp_launch_check can only be loaded by WP-CLI. Use `wp --require=wp_launch_check.phar`" . PHP_EOL;
	exit(1);
}
include 'phar://wp_launch_check.phar/php/commands/launchcheck.php';
__HALT_COMPILER();
?>
EOB
);

$phar->stopBuffering();

echo "Generated " . DEST_PATH . "\n";
