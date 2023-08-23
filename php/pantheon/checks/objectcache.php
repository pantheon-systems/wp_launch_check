<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Objectcache extends Checkimplementation {
	public $check_all_plugins;

	public function init() {
		$this->name = 'objectcache';
		$this->action = 'No action required';
		$this->description = 'Checking the object caching is on and responding.';
		$this->score = 0;
		$this->result = '';
		$this->label = 'Object Cache';
		$this->alerts = array();
		self::$instance = $this;
		return $this;
	}

	public function run() {
		global $redis_server;
		$object_cache_file = WP_CONTENT_DIR . '/object-cache.php';
		if (!file_exists($object_cache_file)) {
			$this->alerts[] = array("message"=> "No object-cache.php exists", "code" => 1);
		} else {
			$this->alerts[] = array("message"=> "object-cache.php exists", "code" => 0);
		}
		if ( defined( 'WP_REDIS_OBJECT_CACHE' )) {
			$this->alerts[] = array('message'=> 'WP Redis for object caching was found. We recommend using Object Cache Pro as a replacement. <a href="https://docs.pantheon.io/guides/object-cache-pro/installing-configuring/" target="_blank">Learn More</a> about how to install Object Cache Pro.', 'code' => 1);
		} elseif ( ! defined( 'WP_REDIS_CONFIG' ) || ! WP_REDIS_CONFIG ) {
			$this->alerts[] = array("message"=> 'Use Object Cache Pro to speed up your backend. <a href="https://docs.pantheon.io/guides/object-cache-pro/installing-configuring/" target="_blank">Learn More</a>', "code" => 1);
		} else {
			$this->alerts[] = array("message"=> "Object Cache Pro found", "code" => 0);
		}

		return $this;
	}

	public function message(Messenger $messenger) {
			if (!empty($this->alerts)) {
				$total = 0;
				$rows = array();
				// this is dumb and left over from the previous iterationg. @TODO move scoring to run() method
				foreach ($this->alerts as $alert) {
					$total += $alert['code'];
					$alert['class'] = 'ok';
					if (-1 === $alert['code']) {
						$alert['class'] = 'fail';
					} elseif( 2 > $alert['code']) {
						$alert['class'] = 'warning';
					}
					$rows[] = $alert;
				}
				$avg = $total/count($this->alerts);
				$this->result = View::make('checklist', array('rows'=> $rows) );
				$this->score = $avg;
				$this->action = "You should use Object Cache Pro";
		}
		$messenger->addMessage(get_object_vars($this));
	}
}
