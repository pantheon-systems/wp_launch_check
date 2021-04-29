<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Themes extends Checkimplementation {
	public $name = 'themes';
	public $check_all_themes;

	public function __construct($check_all_themes) {
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
		$this->alerts = array();
		self::$instance = $this;
		return $this;
	}

	public function run() {
		if (!function_exists('wp_get_themes')) {
			require_once \WP_CLI::get_config('path') . '/wp-includes/theme.php';
		}
		$all_themes = Utils::sanitize_data( wp_get_themes() );
		$update = Utils::sanitize_data( get_theme_updates() );
		$report = array();
		foreach( $all_themes as $theme_path => $data ) {
			$slug = $theme_path;
			if (stripos($theme_path,'/')) {
				$slug = substr($theme_path, 0, stripos($theme_path,'/'));
			}

			$vulnerable = $this->is_vulnerable($slug, $data['Version']);

			$needs_update = 0;
			$available = '-';
			if (isset($update[$theme_path])) {
				$needs_update = 1;
				$available = $update[$theme_path]->update->new_version;
			}
			if ( false === $vulnerable ) {
				$vulnerable = "None";
			} else {
				$vulnerable = sprintf('<a href="https://wpvulndb.com/themes/%s" target="_blank" >more info</a>', $slug );
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
	 * Checks the theme slug against the vulnerability db
	 * @param $theme_slug string (required) string representing the theme slug
	 *
	 * @return array containing vulnerability info or false
	 */
	protected function getThemeVulnerability($theme_slug )
	{
		// Get the vulnerability API token from the platform
		$wpvulndb_api_token = getenv('PANTHEON_WPVULNDB_API_TOKEN');

		// Throw an exception if there is no token
		if( false === $wpvulndb_api_token || empty( $wpvulndb_api_token ) ) {
			throw new \Exception('No WP Vulnerability DB API Token. Please ensure the PANTHEON_WPVULNDB_API_TOKEN environment variable is set');
		}

		// Set the request URL to the requested theme
		$url = 'https://wpvulndb.com/api/v3/themes/' . $theme_slug;

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

		// Return false if the specified theme slug is not in the result
		if( ! isset( $result[$theme_slug] ) ) {
			return false;
		}

		// Return the requested theme vulnerability info
		return $result[$theme_slug];
	}

	/**
	* Checks a theme by slug and version for vulnerabilities
	* @param $theme_slug string (required) string representing the theme slug
	* @param $current_version string (required) string representing the theme version
	*
	* @return array containing the vulnerability or false
	*/
	public function is_vulnerable($theme_slug, $current_version) {

		// Fetch the theme data if we don't have it already
		if( !isset( $theme_data[$theme_slug] ) ){
			$theme_results = $this->getThemeVulnerability( $theme_slug );

			// Return false if no theme results from the vulnerability API
			if( false === $theme_results ){
				return false;
			}
		}

		// No issues if the theme has no vulnerabilities
		if ( ! isset( $theme_results['vulnerabilities'] ) || empty( $theme_results['vulnerabilities'] ) ) {
			return false;
		}


		// Loop through all vulnerabilities
		foreach ( $theme_results['vulnerabilities'] as $vulnerability ) {

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
				'slug'=>"Theme",
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
			$rendered .= sprintf("Found %d themes needing updates and %d known vulnerabilities ... \n".PHP_EOL, $count_update, $count_vuln);
			$rendered .= View::make('table', array('headers'=>$headers,'rows'=>$rows));

			$this->result .= $rendered;
			if ($count_update > 0) {
				$this->score = 1;
				$this->action = "You should update all out-of-date themes";
			}

			if ($count_vuln > 0) {
				$this->score = 2;
				$this->action = "Update themes to fix vulnerabilities";
			}
		} else {
			$this->result .= "No themes found.";
		}
		$messenger->addMessage(get_object_vars($this));
	}
}
