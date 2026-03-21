<?php

use Behat\Behat\Context\Context;
use WP_CLI\Tests\Context\FeatureContext as WpCliFeatureContext;

/**
 * Custom feature context for WP Launch Check.
 *
 * Extends wp-cli-tests FeatureContext and adds project-specific steps.
 */
class FeatureContext extends WpCliFeatureContext implements Context {

	/**
	 * Track whether we should skip WP version checks.
	 *
	 * @var bool
	 */
	private $skipWpVersionCheck = false;

	/**
	 * Check if current WP version is not the latest.
	 *
	 * Sets a flag to skip version-dependent assertions if the installed
	 * version matches the latest available version.
	 *
	 * @Given the current WP version is not the latest
	 */
	public function theCurrentWpVersionIsNotTheLatest() {
		$result         = $this->proc( 'wp core version' )->run();
		$currentVersion = trim( $result->stdout );

		// Fetch latest version from WordPress API
		$url     = 'https://api.wordpress.org/core/version-check/1.7/';
		$context = stream_context_create( [ 'http' => [ 'timeout' => 5 ] ] );
		$json    = @file_get_contents( $url, false, $context );

		if ( false === $json ) {
			// Skip test if we can't determine latest version
			$this->skipWpVersionCheck = true;
			return;
		}

		$data          = json_decode( $json, true );
		$latestVersion = $data['offers'][0]['current'] ?? null;

		if ( empty( $latestVersion ) || $currentVersion === $latestVersion ) {
			$this->skipWpVersionCheck = true;
		}
	}

	/**
	 * Check if we should skip WP version-dependent assertions.
	 *
	 * @return bool
	 */
	public function shouldSkipWpVersionCheck() {
		return $this->skipWpVersionCheck;
	}
}
