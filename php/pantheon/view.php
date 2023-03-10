<?php
namespace Pantheon;

class View {
	static $viewsdir = "/views";

	/**
	 * Searches php files for the provided regex
	 *
	 * @param $view
	 * @param $data
	 * @return false|string an array of matched files or empty if none found
	 */
	static function make($view, $data) {
		ob_start();
		if (file_exists(__DIR__.self::$viewsdir."/$view.php")) {
			extract($data);
			include(__DIR__.self::$viewsdir."/$view.php");
		}
		return ob_get_clean();
	}

}
