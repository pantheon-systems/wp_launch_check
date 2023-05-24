<?php
/**
 * WP Launch Check common functions
 *
 * @package wp_launch_check
 */

namespace Pantheon\Checks\Common;

/**
 * Get a WordPress vulnerability API token if one is defined and we're in the right environment.
 * Copied from wp_launch_check/php/pantheon/checks/plugins.php
 * Uses the WPSCAN_API_TOKEN constant if defined.
 *
 * @return string
 * @todo Replace this with a Patchstack API token.
 */
function get_wp_vuln_api_token() {
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
