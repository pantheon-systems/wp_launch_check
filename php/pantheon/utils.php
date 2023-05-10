<?php
namespace Pantheon;

use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Finder\Finder;

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
	 * @param mixed $data Data to be sanitized
	 * @param string|function $sanitizer_function Name of or the actual function with which to sanitize data
	 * @return array|object|string
	 */
	public static function sanitize_data($data, $sanitizer_function = 'htmlspecialchars') {
		if ( is_array( $data ) || is_object( $data ) ) {
			$sanitized_data = array_combine(
				array_map($sanitizer_function, array_keys((array)$data)),
				array_map('self::sanitize_data', array_values((array)$data))
			);
			return is_object( $data ) ? (object)$sanitized_data : $sanitized_data;
		} elseif ( is_integer( $data ) ) {
			return (string)$data;
		} elseif ( is_string( $data ) ) {
			if ( ! empty( $data ) ) {
				$dom = new \DOMDocument;
				$dom->loadHTML( $data );
				$anchors = $dom->getElementsByTagName('a');

				// Bail if our string does not only contain an anchor tag.
				if ( 0 === $anchors->length ) {;
					return $sanitizer_function($data);
				}

				$href = $anchors[0]->getAttribute('href');
				$sanitized_href = call_user_func($sanitizer_function, $href);
				$sanitized_link_text = call_user_func($sanitizer_function, $anchors[0]->textContent);
				
				// Rebuild anchor tags to ensure there are no injected attributes.
				$rebuilt_link = '<a href="' . $sanitized_href . ' target="_blank"">' . $sanitized_link_text . '</a>';
				return $rebuilt_link;
			}
		}

		return $sanitizer_function($data);
	}

}
