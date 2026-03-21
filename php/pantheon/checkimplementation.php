<?php
namespace Pantheon;

abstract class Checkimplementation {
	public $action;
	public $description;
	public $score;
	public $result;
	public $label;
	public static $instance;

	public function __construct() {
		self::$instance = $this;

	}

}
