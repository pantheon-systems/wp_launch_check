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

define("WP_CLI_ROOT", dirname(__FILE__).'/..');

// register our autoloader
spl_autoload_register(function($class) {
	if (class_exists($class)) return $class;
	$class = strtolower($class);
	if (strstr($class,"pantheon")) {
		$class = str_replace('\\','/',$class);
		$path = WP_CLI_ROOT."/php/".$class.'.php';
		if (file_exists($path)) {
			require_once($path);
		}
	}
});
require dirname( __FILE__ ) . '/../vendor/autoload.php';
