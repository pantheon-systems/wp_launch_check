<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Themes extends Checkimplementation {
	public $name = 'themes';
	public $check_all_themes;
	public $alerts = array();

	public function __construct($check_all_themes) {
		require_once __DIR__ . '/namespace.php';

		$this->check_all_themes = $check_all_themes;
	}

	public function init() {
		$this->action = 'No action required';
		$this->description = 'Looking for theme info';
		if ( $this->check_all_themes ) {
			$this->description .= ' ( active and inactive )';
		} else {
			$this->description .= ' ( active only )';
		}
		$this->score = 0;
		$this->result = '';
		$this->label = 'Themes';
		self::$instance = $this;
		return $this;
	}

	public function run() {
		if (!function_exists('wp_get_themes')) {
			require_once \WP_CLI::get_config('path') . '/wp-includes/theme.php';
		}
		$current_theme = wp_get_theme();
		$all_themes = Utils::sanitize_data( wp_get_themes() );
		$update = Utils::sanitize_data( get_theme_updates() );
		$report = array();

		foreach( $all_themes as $theme_path => $data ) {
			$slug = $theme_path;
			if (stripos($theme_path,'/')) {
				$slug = substr($theme_path, 0, stripos($theme_path,'/'));
			}

			// Check if we only want to check the active theme.
			if (!$this->check_all_themes) {
				// If theme list index doesn't match current theme, skip.
				if ($current_theme->stylesheet !== $slug) {
					continue;
				}
			}

			$data = wp_get_theme($slug);
			$version = $data->version;
			$needs_update = 0;
			$available = '-';

			if (isset($update[$theme_path])) {
				$needs_update = 1;
				$available = $update[$slug]->update["new_version"];
			}

			$report[$slug] = array(
				'slug' => $slug,
				'installed' => (string) $version,
				'available' => (string) $available,
				'needs_update' => (string) $needs_update,
			);
		}
		$this->alerts = $report;
	}

	public function message(Messenger $messenger) {
		if (empty($this->alerts)) {
			// Nothing to do. Return early.
			$this->result .= __( 'No themes found' );
			$messenger->addMessage(get_object_vars($this));
			return;
		}

		$theme_message = __( 'You should update all out-of-date themes' );
		$headers = array(
			'slug' => __( 'Theme' ),
			'installed' => __( 'Current' ),
			'available' => __( 'Available' ),
			'needs_update' => __( 'Needs Update' ),
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

		$updates_message = $count_update === 1 ? __( 'Found one theme needing updates' ) : sprintf( _n( 'Found %d theme needing updates', 'Found %d themes needing updates', $count_update ), $count_update );
		$result_message = $updates_message . ' ...';
		$rendered = PHP_EOL;
		$rendered .= "$result_message \n" .PHP_EOL;
		$rendered .= View::make('table', array('headers'=>$headers,'rows'=>$rows));

		$this->result .= $rendered;
		if ($count_update > 0) {
			$this->score = 1;
			$this->action = $theme_message;
		}

		$messenger->addMessage(get_object_vars($this));
	}
}
