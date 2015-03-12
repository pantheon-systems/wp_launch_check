<?php
namespace Pantheon;

class View {
	static $viewsdir = "/views";

  /**
  * Searches php files for the provided regex
  *
  * @param $dir string directory to start from
  * @param $regex string undelimited pattern to match
  *
  * @return array an array of matched files or empty if none found
  **/

  static function make($view, $data) {
		ob_start();
		if (file_exists(__DIR__.self::$viewsdir."/$view.php")) {
			extract($data);
			include(__DIR__.self::$viewsdir."/$view.php");
		}
		$out = ob_get_clean();
		return $out;
	}

}
