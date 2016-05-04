<?php

namespace Pantheon\Checks;

use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Config extends Checkimplementation {

	private $run_once = false;
	private $valid_db = true;

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

		// Can't be run twice, because it needs to run without WP loaded
		if ( $this->run_once ) {
			return $this;
		}

		$runner = \WP_CLI::get_runner();
		$wp_config = $runner->get_wp_config_code();
		eval( $wp_config );

		$this->checkWPCache();
		$this->checkNoServerNameWPHomeSiteUrl();
		$this->checkUsesEnvDBConfig();

		// wp-config is going to be loaded again, and we need to avoid notices
		if ( $this->valid_db ) {
			@$runner->load_wordpress();
		}
		$this->run_once = true;

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

	public function checkUsesEnvDBConfig() {

		// Check is only applicable in the Pantheon environment
		if ( empty( $_ENV['PANTHEON_ENVIRONMENT'] ) ) {
			return;
		}
		
		$compared_values = array(
			'DB_NAME',
			'DB_USER',
			'DB_PASSWORD',
		);
		$different_values = array();
		foreach( $compared_values as $key ) {
			if ( constant( $key ) != $_ENV[ $key ] ) {
				$different_values[] = $key;
			}
		}
		if ( constant( 'DB_HOST' ) != $_ENV['DB_HOST'] . ':' . $_ENV['DB_PORT'] ) {
			$different_values[] = 'DB_HOST';
		}

		if ( $different_values ) {
			$this->alerts[]  = array(
				'code'  => 2,
				'class' => 'warning',
				'message' => 'Some database constants differ from their expected $_ENV values: ' . implode( ', ' , $different_values ),
			);
			$this->valid_db = false;
		} else {
			$this->alerts[]  = array(
				'code'  => 0,
				'class' => 'ok',
				'message' => implode( ', ', array_merge( $compared_values, array( 'DB_HOST' ) ) ) . ' are set to their expected $_ENV values.',
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
