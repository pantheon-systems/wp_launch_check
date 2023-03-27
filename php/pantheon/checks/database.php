<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Database extends Checkimplementation {
	public $check_all_plugins;
	public $performance_tables;
	public $alerts;

	public function init() {
		$this->name = 'database';
		$this->action = 'No action required';
		$this->description = 'Checking the database for issues.';
		$this->score = 0;
		$this->result = '';
		$this->label = 'Database';
		$this->alerts = array();
		$this->performance_tables = array();
		self::$instance = $this;
		return $this;
	}

	public function run() {
		$this->countRows();
		$this->checkInnoDB();
		$this->checkTransients();
		$this->checkDatabaseSize();
		$this->checkIndexPerformance();
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
			if ( $table->TABLE_NAME === $wpdb->prefix . 'options' ) {
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

	public function checkDatabaseSize() {
		global $wpdb;
		$query = "SELECT table_name AS `table`,
       			round(((data_length + index_length) / 1024 / 1024), 2) `size`,
				table_rows AS `rows`
			  FROM information_schema.TABLES
			  WHERE table_schema = '$wpdb->dbname'
			  ORDER BY (data_length + index_length) DESC";

		$tables = $wpdb->get_results($query);
		$headers = array('Table', 'Size (MB)', 'Rows');
		$rows = array();
		foreach( $tables as $table ) {
			$table_size = number_format($table->size, 2);
			$class = ($table_size >= 5000) ? 'error' : (($table_size >= 1000) ? 'warning' : 'ok');
			$row = array();
			$row['class'] = $class;
			$row['data'] = array(
				$table->table,
				$table_size,
				number_format($table->rows)
			);
			$rows[] = $row;
		}

		if ($rows) {
			$this->performance_tables[] = array(
				'title' => 'Database Size Overview',
				'headers' => $headers,
				'rows' => $rows,
			);
		}
	}

	public function checkIndexPerformance() {
		global $wpdb;
		$query = "SELECT
            t.table_name AS `table`,
            i.index_name AS `index`,
            round((100 * (1 - (i.cardinality / t.table_rows))), 2) AS `index_select`,
            round(((t.data_length + t.index_length) / 1024 / 1024), 2) `size`,
            t.index_length / i.cardinality `avg_length`,
            t.table_rows AS `rows`
        FROM information_schema.TABLES t
            JOIN information_schema.STATISTICS i ON t.table_name = i.table_name AND t.table_schema = i.table_schema
        WHERE t.table_schema = '$wpdb->dbname'
            AND i.index_name != 'PRIMARY'
            AND i.index_name NOT LIKE '%\_unique'
        ORDER BY `table` ASC, `index` ASC";

		$indexes = $wpdb->get_results($query);
		$headers = array('Table', 'Index Name', 'Index Selectivity (%)', 'Rows', 'Avg. Length');
		$rows = array();
		foreach( $indexes as $index ) {
			$index_select = number_format($index->index_select, 2);

			$class = 'ok';
			// Rule of thumb for minimum row count to create an index.
			if ($index->rows > 2000) {
				// Check performance of index based on selectivity.

				// RED: Higher than 60% and less than 10%
				// Selectivity is the ratio of the number of distinct index key values to the number of rows in the table.
				// A higher selectivity means that the index is more selective and can narrow down the search for a particular value more quickly.
				// However, if the selectivity is too high (e.g., close to 100%), it means that the index is not very useful
				// for selective queries because it matches a large percentage of rows in the table.
				if ($index_select >= 60 || $index_select <= 10) {
					$class = 'error';
				}
				// WARNING: Higher than 10% (less than 20%) and higher than 30% (but less than 60%).
				 else if (($index_select > 10 && $index_select < 20) || ($index_select > 30 && $index_select < 60)) {
					$class = 'warning';
				}
				 // Sweet spot for selectivity is around 20%-30%, but merely a guide.
			}

			$row = array();
			$row['class'] = $class;
			$row['data'] = array(
				$index->table,
				$index->index,
				$index_select,
				$index->rows,
				number_format($index->avg_length),
			);
			$rows[] = $row;
		}

		// Information about the columns in the table.
		$list = array(
			'<strong>Table</strong>: The name of the table with the index applied.',
			'<strong>Index</strong>: The unique name of the index.',
			'<strong>Index Selectivity</strong>: Selectivity is the ratio of the number of distinct index key values to the number of rows in the table.',
			'<strong>Rows</strong>: The number of rows in the table.',
			'<strong>Average Length</strong>: This column shows the average length of each index entry, in bytes. A longer average length can lead to larger index sizes and slower queries due to increased I/O operations.',
		);

		if ($rows) {
			$this->performance_tables[] = array(
				'title' => 'Database Index Performance Overview',
				'headers' => $headers,
				'rows' => $rows,
				'list' => $list,
			);
		}
	}

	public function message(Messenger $messenger) {
			if (!empty($this->alerts)) {
				$total = 0;
				$rows = array();
				// this is dumb and left over from the previous iteration. @TODO move scoring to run() method
				foreach ($this->alerts as $alert) {
					$total += $alert['code'];
					$rows[] = $alert;
				}
				$avg = $total/count($this->alerts);
				$this->result = View::make('checklist', array('rows'=> $rows) );
				$this->score = round($avg);
		}
			if (!empty($this->performance_tables)) {
				foreach ($this->performance_tables as $table) {
					$this->result .= "<h4>" . $table['title'] . "</h4>";
					if (!empty($table['list'])) {
						$this->result .= View::make('list', ['rows' => $table['list'], 'type' => 'ul']);
						$this->result .= "<br/>";
					}
					$this->result .= View::make('table', array('headers' => $table['headers'], 'rows' => $table['rows'], 'fixed' => TRUE));
				}
			}
		$messenger->addMessage(get_object_vars($this));
	}
}
