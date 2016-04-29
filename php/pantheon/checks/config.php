<?php

namespace Pantheon\Checks;

use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Config extends Checkimplementation {

	public function init() {
		$this->name = 'config';
		$this->action = 'No action required';
		$this->description = 'Checking for a properly-configured wp-config';
		$this->score = 0;
		$this->result = '';
		$this->label = 'Config';
		$this->alerts = array();
		self::$instance = $this;
		return $this;
	}

	public function run() {
		$this->checkWPCache();
		$this->checkNoServerNameWPHomeSiteUrl();
		return $this;
	}

	public function checkWPCache() {
		if (defined('WP_CACHE') && WP_CACHE ) {
			$this->alerts[] = array(
					'code' => 1,
					'class' => 'warning',
					'message' => 'The WP_CACHE constant is set to true, and should be removed. Page cache plugins are unnecessary on Pantheon.',
				);
		} else {
			$this->alerts[]  = array(
				'code'  => 0,
				'class' => 'ok',
				'message' => 'WP_CACHE not found or is set to false.',
			);
		}
	}

	public function checkNoServerNameWPHomeSiteUrl() {
		$wp_config = \WP_CLI::get_runner()->get_wp_config_code();
		if ( preg_match( '#define\(.+WP_(HOME|SITEURL).+\$_SERVER.+SERVER_NAME#', $wp_config ) ) {
			$this->alerts[]  = array(
				'code'  => 0,
				'class' => 'warning',
				'message' => "\$_SERVER['SERVER_NAME'] appears to be used to define WP_HOME or WP_SITE_URL, which will be unreliable on Pantheon.",
			);
		} else {
			$this->alerts[]  = array(
				'code'  => 0,
				'class' => 'ok',
				'message' => "Verified that \$_SERVER['SERVER_NAME'] isn't being used to define WP_HOME or WP_SITE_URL.",
			);
		}
	}

	public function message(Messenger $messenger) {
		if (!empty($this->alerts)) {
			$total = 0;
			$rows = array();
			foreach ($this->alerts as $alert) {
				$total += $alert['code'];
			}
			$avg = $total/count($this->alerts);
			$this->result = View::make('checklist', array('rows'=> $this->alerts) );
			$this->score = $avg;
		}
		$messenger->addMessage(get_object_vars($this));
	}

}
