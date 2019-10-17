<?php
namespace Pantheon;

use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Finder\Finder;
use \Pantheon\Utils as Pantheon;
use \WP_CLI\Utils as WPCLIUtils;

class Utils {
	static $fs;

	/**
	* Searches php files for the provided regex
	*
	* @param $dir string directory to start from
	* @param $regex string undelimited pattern to match
	*
	* @return array an array of matched files or empty if none found
	**/
	public static function search_php_files($dir, $regex) {
		$fs = self::load_fs();
		$finder = new Finder();

		// find all files ending in PHP
		$files = $finder->files()->in($dir)->name("*.php");
		$alerts = array();

		foreach ( $files as $file ) {
			if ( \WP_CLI::get_config('debug') ) {
				\WP_CLI::line( sprintf("-> %s",$file->getRelativePathname()) );
			}

			if ( preg_match('#'.$regex.'#s',$file->getContents()) !== 0 ) {
				$alerts[] = $file->getRelativePathname();
			}
		}
		return $alerts;

	}

	public static function load_fs() {
		if ( self::$fs ) {
			return self::$fs;
		}

		self::$fs = new filesystem();
		return self::$fs;
	}

	/**
	 * Sanitizes data and keys recursively
	 *
	 * @param array|object|string $data
	 * @return array|object|string
	 */
	public static function sanitize_data($data, $sanitizer_function = 'esc_attr') {
		if ( is_array( $data ) || is_object( $data ) ) {
			$sanitized_data = array_combine(
				array_map($sanitizer_function, array_keys((array)$data)),
				array_map('self::sanitize_data', array_values((array)$data))
			);
			return is_object( $data ) ? (object)$sanitized_data : $sanitized_data;
		}

		return $sanitizer_function($data);
	}

}
