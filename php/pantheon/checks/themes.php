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
		$should_check_vulnerabilities = Common\get_wp_vuln_api_token();
		$vulnerable = false;

		foreach( $all_themes as $theme_path => $data ) {
			$slug = $theme_path;
			if (stripos($theme_path,'/')) {
				$slug = substr($theme_path, 0, stripos($theme_path,'/'));
			}

			// Check if we only want to scan the active theme.
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

			// If we're checking for vulnerabilities, do stuff.
			if ( $should_check_vulnerabilities ) {
				$vulnerable = $this->is_vulnerable($slug, $version);

				if ( $vulnerable ) {
					// Todo: Replace this link with one to Patchstack.
					$vulnerable = sprintf('<a href="https://wpscan.com/themes/%s" target="_blank" >more info</a>', $slug );
				} else {
					$vulnerable = "None";
				}

				$report[ $slug ]['vulnerable'] = $vulnerable;
			}
		}
		$this->alerts = $report;
	}

	/**
	 * Get a WordPress vulnerability API token if one is defined and we're in the right environment.
	 * Copied from wp_launch_check/php/pantheon/checks/plugins.php
	 * Uses the WPSCAN_API_TOKEN constant if defined.
	 *
	 * @return string|false
	 * @todo Replace this with a Patchstack API token.
	 */
	protected function getWpVulnApiToken() {
		if ( defined( 'WPSCAN_API_TOKEN' ) ) {
			// Don't use WPSCAN if PANTHEON_WPSCAN_ENVIRONMENTS have not been specified.
			if( ! defined( 'PANTHEON_WPSCAN_ENVIRONMENTS' ) ) {
				return '';
			}

			$environments = ( ! is_array( PANTHEON_WPSCAN_ENVIRONMENTS ) ) ? explode( ',', PANTHEON_WPSCAN_ENVIRONMENTS ) : PANTHEON_WPSCAN_ENVIRONMENTS;

			// Only run WPSCAN on the specified environments unless it's been configured to run on all (*).
			if ( in_array( getenv( 'PANTHEON_ENVIRONMENT' ), $environments, true ) || in_array( '*', $environments, true ) ) {
				return WPSCAN_API_TOKEN;
			}
		}

		// TODO: Replace this PANTHEON_WPVULNDB_API_TOKEN with a new Patchstack API token.
		// return getenv( 'PANTHEON_WPVULNDB_API_TOKEN' );
		return '';
	}

	/**
	 * Checks the theme slug against the vulnerability db
	 * @param $theme_slug string (required) string representing the theme slug
	 *
	 * @return array containing vulnerability info or false
	 * @throws \Exception
	 * @todo Refactor this to use the Patchstack API
	 */
	protected function getThemeVulnerability($theme_slug ) {
		$wpvulndb_api_token = Common\get_wp_vuln_api_token();

		// Fail silently if there is no API token.
		if( false === $wpvulndb_api_token || empty( $wpvulndb_api_token ) ) {
			return false;
		}

		// Set the request URL to the requested theme
		$url = 'https://wpscan.com/api/v3/themes/' . $theme_slug;

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
	 * @throws \Exception
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
		if ( empty( $theme_results['vulnerabilities'] ) ) {
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
			$should_check_vulnerabilities = Common\get_wp_vuln_api_token();
			$theme_message = __( 'You should update all out-of-date themes' );
			$vuln_message = __( 'Update themes to fix vulnerabilities' );
			$no_themes_message = __( 'No themes found' );
			$headers = array(
				'slug' => __( 'Theme' ),
				'installed' => __( 'Current' ),
				'available' => __( 'Available' ),
				'needs_update' => __( 'Needs Update' ),
			);
			if ( $should_check_vulnerabilities ) {
				$headers['vulnerable'] = __( 'Vulnerable' );
			}

			$rows = array();
			$count_update = 0;
			$count_vuln = 0;
			foreach( $this->alerts as $alert ) {
				$class = 'ok';
				if ($alert['needs_update']) {
					$class = 'warning';
					$count_update++;
				}
				if ( $should_check_vulnerabilities && 'None' !== $alert['vulnerable']) {
					$class = 'error';
					$count_vuln++;
				}
				$rows[] = array('class'=>$class, 'data' => $alert);
			}

			$updates_message = $count_update === 1 ? __( 'Found one theme needing updates' ) : sprintf( _n( 'Found %d theme needing updates', 'Found %d themes needing updates', $count_update ), $count_update );
			$result_message = ! $should_check_vulnerabilities ?
				// Not checking vulnerabilities message.
				$updates_message . ' ...':
				// Checking vulnerabilities message.
				$updates_message . ' ' .
				( $count_vuln === 1 ? __( 'Also found one theme with known vulnerabilities ...' ) : sprintf( _n( 'Also found %d theme with known vulnerabilities ...', 'Also found %d themes with known vulnerabilities ...', $count_vuln ), $count_vuln ) );
			$rendered = PHP_EOL;
			$rendered .= "$result_message \n" .PHP_EOL;
			$rendered .= View::make('table', array('headers'=>$headers,'rows'=>$rows));

			$this->result .= $rendered;
			if ($count_update > 0) {
				$this->score = 1;
				$this->action = $theme_message;
			}

			if ( $should_check_vulnerabilities && $count_vuln > 0 ) {
				$this->score = 2;
				$this->action = $vuln_message;
			}
		} else {
			$this->result .= $no_themes_message;
		}
		$messenger->addMessage(get_object_vars($this));
	}
}
