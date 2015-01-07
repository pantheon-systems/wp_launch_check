<?php
/**
 * Stubbed WP_CLI
**/
class WP_CLI {
	
	static function get_config( $key =null ) {
		return true;
	}

	static function line( $message ) {
		echo $message;
	}
	
}
require dirname( __FILE__ ) . '/../vendor/autoload.php';
require dirname( __FILE__ ) . '/../php/pantheon/utils.php';

