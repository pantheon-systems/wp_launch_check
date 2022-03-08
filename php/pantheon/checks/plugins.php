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

			$vulnerable = $this->is_vulnerable($slug, $data['Version']);

			$needs_update = 0;
			$available = '-';
			if (isset($update[$plugin_path])) {
				$needs_update = 1;
				$available = $update[$plugin_path]->update->new_version;
			}
			if ( false === $vulnerable ) {
				$vulnerable = "None";
			} else {
				$vulnerable = sprintf('<a href="https://wpvulndb.com/plugins/%s" target="_blank" >more info</a>', $slug );
			}

			$report[$slug] = array(
				'slug' => $slug,
				'installed' => (string) $data['Version'],
				'available' => (string) $available,
				'needs_update' => (string) $needs_update,
				'vulnerable'  => $vulnerable,
			);
		}
		$this->alerts = $report;
	}

	/**
	 * Checks the plugin slug against the vulnerability db
	 * @param $plugin_slug string (required) string representing the plugin slug
	 *
	 * @return array containing vulnerability info or false
	 */
	protected function getPluginVulnerability( $plugin_slug )
	{
		// Get the vulnerability API token from the platform
		$wpvulndb_api_token = getenv('PANTHEON_WPSCAN_API_TOKEN');

		// Throw an exception if there is no token
		if( false === $wpvulndb_api_token || empty( $wpvulndb_api_token ) ) {
			throw new \Exception('No WP Vulnerability DB API Token. Please ensure the PANTHEON_WPSCAN_API_TOKEN environment variable is set');
		}

		// Set the request URL to the requested plugin
		$url = 'https://wpvulndb.com/api/v3/plugins/' . $plugin_slug;

		// Add the token to the headers
		$headers = array(
			'Content-Type: application/json',
			'User-Agent: pantheon/wp_launch_check',
			'Authorization: Token token=' . $wpvulndb_api_token
		);

		// Make the request to the API
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		curl_close($ch);

		// Return false if no result from the API
		if( false === $result ) {
			return false;
		}

		// Decode the result from the API
		$result = json_decode( $result, true );

		// Return false if the specified plugin slug is not in the result
		if( ! isset( $result[$plugin_slug] ) ) {
			return false;
		}

		// Return the requested plugin vulnerability info
		return $result[$plugin_slug];
	}

	/**
	* Checks a plugin by slug and version for vulnerabilities
	* @param $plugin_slug string (required) string representing the plugin slug
	* @param $current_version string (required) string representing the plugin version
	*
	* @return array containing the vulnerability or false
	*/
	public function is_vulnerable($plugin_slug, $current_version) {

		// Fetch the plugin data if we don't have it already
		if( !isset( $plugin_data[$plugin_slug] ) ){
			$plugin_results = $this->getPluginVulnerability( $plugin_slug );

			// Return false if no plugin results from the vulnerability API
			if( false === $plugin_results ){
				return false;
			}

		}

		// No issues if the plugin has no vulnerabilities
		if ( ! isset( $plugin_results['vulnerabilities'] ) || empty( $plugin_results['vulnerabilities'] ) ) {
			return false;
		}


		// Loop through all vulnerabilities
		foreach ( $plugin_results['vulnerabilities'] as $vulnerability ) {

			// If the vulnerability hasn't been fixed, then there's an issue
			if ( ! isset( $vulnerability['fixed_in'] ) ) {
				return $vulnerability;
			}

			// If the vulnerability has been fixed, but not in the current version, there's an issue
			if ( version_compare( $vulnerability['fixed_in'], $current_version,'>' ) ){
				return $vulnerability;
			}

		}

		// If we get this far the current version has no vulnerabilities
		return false;
	}

	public function message(Messenger $messenger) {
		if (!empty($this->alerts)) {
			$headers = array(
				'slug'=>"Plugin",
				'installed'=>"Current",
				'available' => "Available",
				'needs_update'=>"Needs Update",
				'vulnerable'=>"Vulnerabilities"
			);
			$rows = array();
			$count_update = 0;
			$count_vuln = 0;
			foreach( $this->alerts as $alert ) {
				$class = 'ok';
				if ($alert['needs_update']) {
					$class = 'warning';
					$count_update++;
				}
				if ('None' != $alert['vulnerable']) {
					$class = 'error';
					$count_vuln++;
				}
				$rows[] = array('class'=>$class, 'data' => $alert);
			}

			$rendered = PHP_EOL;
			$rendered .= sprintf("Found %d plugins needing updates and %d known vulnerabilities ... \n".PHP_EOL, $count_update, $count_vuln);
			$rendered .= View::make('table', array('headers'=>$headers,'rows'=>$rows));

			$this->result .= $rendered;
			if ($count_update > 0) {
				$this->score = 1;
				$this->action = "You should update all out-of-date plugins";
			}

			if ($count_vuln > 0) {
				$this->score = 2;
				$this->action = "Update plugins to fix vulnerabilities";
			}
		} else {
			$this->result .= "No plugins found.";
		}
		$messenger->addMessage(get_object_vars($this));
	}
}
