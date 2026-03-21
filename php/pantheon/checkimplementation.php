<?php
namespace Pantheon;

abstract class Checkimplementation {
	public $action;
	public $alerts = [];
	public $description;
	public $label;
	public $name;
	public $result;
	public $score;
	public static $instance;

	public function __construct() {
		self::$instance = $this;

	}

}
