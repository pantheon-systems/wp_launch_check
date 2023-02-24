<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Database extends Checkimplementation {
	public $check_all_plugins;

	public function init() {
		$this->name = 'database';
		$this->action = 'No action required';
		$this->description = 'Checking the database for issues.';
		$this->score = 0;
		$this->result = '';
		$this->label = 'Database';
		$this->alerts = array();
		self::$instance = $this;
		return $this;
	}

	public function run() {
		$this->countRows();
		$this->checkInnoDB();
		$this->checkTransients();
		return $this;
	}

	protected function getTables() {
		global $wpdb;
		if ( empty($this->tables) ) {
			$query = "select TABLES.TABLE_NAME, TABLES.TABLE_SCHEMA, TABLES.TABLE_ROWS, TABLES.DATA_LENGTH, TABLES.ENGINE from information_schema.TABLES where TABLES.TABLE_SCHEMA = '%s'";
			$tables = Utils::sanitize_data( $wpdb->get_results( $wpdb->prepare( $query, DB_NAME ) ) );
			foreach ( $tables as $table ) {
				$this->tables[$table->TABLE_NAME] = $table;
			}
		}
		return $this->tables;
	}

	protected function countRows() {
		global $wpdb;
		foreach ( $this->getTables() as $table ) {
			$this->tables[$table->TABLE_NAME] = $table;
			if ( $table->TABLE_NAME == $wpdb->prefix . 'options' ) { 
				$options_table = $table;
				break;
			}
		} 
		if ($options_table->TABLE_ROWS > 5000) {
			$this->alerts[] = array('code'=>1, 'message'=> sprintf("Found %s rows in options table which is more than recommended and can cause performance issues", $options_table->TABLE_ROWS), 'class'=>'warning');
		} else {
			$this->alerts[] = array('code'=>0, 'message'=> sprintf("Found %s rows in the options table.", $options_table->TABLE_ROWS), 'class'=>'ok');
		} 

		$autoloads = $wpdb->get_results("SELECT * FROM " . $wpdb->options . " WHERE autoload = 'yes'");
		if ( 1000 < count($autoloads) ) {
			$this->alerts[] = array(
				'code'=>1,
				'message'=> sprintf("Found %d options being autoloaded, consider autoloading only necessary options", count($autoloads)),
				'class'=> 'fail',
			);
		} else {
			$this->alerts[] = array(
				'code'=>0,
				'message'=> sprintf("Found %d options being autoloaded.", count($autoloads)),
				'class'=> 'ok',
			);
		}
	}

	protected function checkInnoDb() {
		$not_innodb = $innodb = array();
		foreach ($this->getTables() as $table) {
			 if ("InnoDB" == $table->ENGINE) {
					$innodb[] = $table->TABLE_NAME;
			 } else {
					$not_innodb[] = $table->TABLE_NAME;
			 }
		}
		if (!empty($not_innodb)) {
			$this->alerts[] = array(
				'code'=> 2,
				'message' => sprintf("The following tables are not InnoDB: %s. To fix, please see documentation: %s", join(', ', $not_innodb), 'https://pantheon.io/docs/articles/sites/database/myisam-to-innodb/' ),
				'class' => 'fail',
			);
		} else {
			$this->alerts[] = array(
				'code' => 0,
				'message' => 'All tables using InnoDB storage engine.',
				'class' => 'ok',
			);
		}
	}

	public function checkTransients() {
		global $wpdb;
		$query = "SELECT option_name,option_value from " . $wpdb->options . " where option_name LIKE '%_transient_%';";
		$transients = $wpdb->get_results($query);
		$this->alerts[] = array( 
			'code'=> 0,
			'message' => sprintf("Found %d transients.", count($transients) ),
			'class'=>'ok',
		);
		$expired = array();
		foreach( $transients as $transient ) { 
			$transient->option_name;
			if ( preg_match( "#^_transient_timeout.*#s", $transient->option_name ) ) {
				if ($transient->option_value < time()) {
					$expired[] = str_replace('_timeout', '', $transient->option_name);
				}
			}
		}
		if ($expired) {
			$this->alerts[] = array(
				'code'  => 1,
				'class' => 'warning',
				'message' => sprintf( 'Found %s expired transients, consider deleting them.', count($expired) ),
			);
		}

	}

	public function message(Messenger $messenger) {
			if (!empty($this->alerts)) {
				$total = 0;
				$rows = array();
				// this is dumb and left over from the previous iterationg. @TODO move scoring to run() method
				foreach ($this->alerts as $alert) {
					$total += $alert['code'];
					$rows[] = $alert;
				}
				$avg = $total/count($this->alerts);
				$this->result = View::make('checklist', array('rows'=> $rows) );
				$this->score = round($avg);
		}
		$messenger->addMessage(get_object_vars($this));
	}
}
