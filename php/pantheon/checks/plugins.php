<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Plugins extends Checkimplementation {
	public $name = 'plugins';
	public $check_all_plugins;

	public function __construct($check_all_plugins) {
		require_once __DIR__ . '/namespace.php';

		$this->check_all_plugins = $check_all_plugins;
	}

	public function init() {
		$this->action = 'No action required';
		$this->description = 'Looking for plugin info';
		if ( $this->check_all_plugins ) {
			$this->description .= ' ( active and inactive )';
		} else {
			$this->description .= ' ( active only )';
		}
		$this->score = 0;
		$this->result = '';
		$this->label = 'Plugins';
		$this->alerts = array();
		self::$instance = $this;
		return $this;
	}

	public function run() {
		if (!function_exists('get_plugins')) {
			require_once \WP_CLI::get_config('path') . '/wp-admin/includes/plugin.php';
		}
		$all_plugins = Utils::sanitize_data( get_plugins() );
		$update = Utils::sanitize_data( get_plugin_updates() );
		$report = array();

		foreach( $all_plugins as $plugin_path => $data ) {
			$slug = $plugin_path;
			if (stripos($plugin_path,'/')) {
				$slug = substr($plugin_path, 0, stripos($plugin_path,'/'));
			}

			$needs_update = 0;
			$available = '-';
			if (isset($update[$plugin_path])) {
				$needs_update = 1;
				$available = $update[$plugin_path]->update->new_version;
			}

			$report[ $slug ] = array(
				'slug' => $slug,
				'installed' => (string) $data['Version'],
				'available' => (string) $available,
				'needs_update' => (string) $needs_update,
			);
		}
		$this->alerts = $report;
	}

	public function message(Messenger $messenger) {
		if (empty($this->alerts)) {
			// Nothing to do. Return early.
			$this->result .= __( 'No plugins found' );
			$messenger->addMessage(get_object_vars($this));
			return;
		}

		$headers = array(
			'slug'=> __( 'Plugin' ),
			'installed'=> __( 'Current' ),
			'available' => __( 'Available' ),
			'needs_update'=> __( 'Needs Update' ),
		);

		$rows = array();
		$count_update = 0;

		foreach( $this->alerts as $alert ) {
			$class = 'ok';
			if ($alert['needs_update']) {
				$class = 'warning';
				$count_update++;
			}

			$rows[] = array('class'=>$class, 'data' => $alert);
		}

		$updates_message = $count_update === 1 ? __( 'Found one plugin needing updates' ) : sprintf( _n( 'Found %d plugin needing updates', 'Found %d plugins needing updates', $count_update ), $count_update );
		$result_message = $updates_message . ' ...';
		$rendered = PHP_EOL;
		$rendered .= "$result_message \n" . PHP_EOL;
		$rendered .= View::make('table', array('headers'=>$headers,'rows'=>$rows));

		$this->result .= $rendered;
		if ($count_update > 0) {
			$this->score = 1;
			$this->action = __( 'You should update all out-of-date plugins' );;
		}

		$messenger->addMessage(get_object_vars($this));
	}
}
