<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class General extends Checkimplementation {

	public function init() {
		$this->name = 'general';
		$this->action = 'No action required';
		$this->description = 'Checking for WordPress best practice';
		$this->score = 0;
		$this->result = '';
		$this->label = 'Best practice';
		$this->alerts = array();
		self::$instance = $this;
		return $this;
	}

	public function run() {
		$this->checkDebug();
		$this->checkCaching();
		$this->checkPluginCount();
		$this->checkUrls();
		$this->checkRegisteredDomains();
		$this->checkCoreUpdates();
	}

	public function checkCaching() {
		if (\is_plugin_active('w3-total-cache/w3-total-cache.php')) {
			$this->alerts[] = array(
				'code' => 2,
				'class' => 'warning',
				'message' => 'W3 Total Cache plugin found. This plugin is not needed on Pantheon and should be removed.',
			);
		} else {  
			$this->alerts[] = array(
				'code' => 0,
				'class' => 'ok',
				'message' => 'W3 Total Cache not found.',
			);
		}
		if (\is_plugin_active('wp-super-cache/wp-cache.php')) {
			$this->alerts[] = array(
				'code' => 2,
				'class' => 'warning',
				'message' => 'WP Super Cache plugin found. This plugin is not needed on Pantheon and should be removed.',
			);
		} else { 
			$this->alerts[] = array(
				'code' => 0,
				'class' => 'ok',
				'message' => 'WP Super Cache not found.',
			);
		}

	}

	public function checkURLS() {
		$siteurl = \get_option('siteurl');
		$home = \get_option('home');
		if ( $siteurl !== $home ) {
			$this->alerts[] = array( 
				'code'  =>  2,
				'class' => 'error',
				'message' => "Site url and home settings do not match. ( 'siteurl'=$siteurl and 'home'=>$home )",
			);
		} else {
			$this->alerts[] = array( 
				'code'  =>  0,
				'class' => 'ok',
				'message' => "Site and home url settings match. ( $siteurl )",
			);
		}
	}

	public function checkPluginCount() {
		$active = get_option('active_plugins');
		$plugins = count($active);
		if ( 100 <= $plugins ) {
			$this->alerts[] = array(  
				'code' => 1,
				'class' => 'warning',
				'message' =>  sprintf('%d active plugins found. You are running more than 100 plugins. The more plugins you run the worse your performance will be. You should uninstall any plugin that is not necessary.', $plugins),
			);
		} else { 
			$this->alerts[] = array(
				'code'  => 0,
				'class' => 'ok',
				'message' => sprintf('%d active plugins found.',$plugins),
			);
		}
	} 

	public function checkDebug() {

		if (defined('WP_DEBUG') AND WP_DEBUG ) {
			if (getenv('PANTHEON_ENVIRONMENT') AND 'live' === getenv('PANTHEON_ENVIRONMENT')) {
				$this->alerts[] = array(
					'code' => 1,
					'class' => 'warning',
					'message' => 'The WP_DEBUG constant is set. You should not run debug mode in production.',
				);
			} else {
				$this->alerts[] = array(
					'code'  => 0,
					'class' => 'ok',
					'message' => 'The WP_DEBUG constant is set. You should remove this before deploying to live.',
				);
			}
		} else {
			$this->alerts[]  = array( 
				'code'  => 0,
				'class' => 'ok',
				'message' => 'WP_DEBUG not found or is set to false.',
			);
		}

		if (!function_exists('is_plugin_active')) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if (\is_plugin_active('debug-bar/debug-bar.php')) {
			if (getenv('PANTHEON_ENVIRONMENT') AND 'live' === getenv('PANTHEON_ENVIRONMENT')) {
				$this->alerts[] = array(
					 'code' => 1,
					 'class' => 'warning',
					 'message' => 'Looks like you are running the debug bar plugin. You should disable this plugin in the live environment'
				);
			}
		}   

	}

	public function checkRegisteredDomains() {
		if ( ! is_multisite() || ! function_exists( 'pantheon_curl' ) || empty( $_ENV['PANTHEON_ENVIRONMENT'] ) ) {
			return;
		}
		$bits = parse_url( 'https://api.live.getpantheon.com:8443/sites/self/state' );
		$response = pantheon_curl( sprintf( '%s://%s%s', $bits['scheme'], $bits['host'], $bits['path'] ), null, $bits['port'] );
		$body = ! empty( $response['body'] ) ? json_decode( $response['body'], true ) : '';
		$pantheon_domains = ! empty( $body['environments'][ $_ENV['PANTHEON_ENVIRONMENT'] ]['urls'] ) ? $body['environments'][ $_ENV['PANTHEON_ENVIRONMENT'] ]['urls'] : array();
		$site_domains = array();
		$it = new \WP_CLI\Iterators\Table( array(
			'table'   => $GLOBALS['wpdb']->blogs,
		) );
		foreach( $it as $blog ) {
			$site_domains[] = parse_url( get_site_url( $blog->blog_id ), PHP_URL_HOST );
		}
		if ( $diff = array_diff( $site_domains, $pantheon_domains ) ) {
			$this->alerts[] = array(
				'code'    => 1,
				'class'   => 'warning',
				'message' => 'One or more WordPress domains are not registered as Pantheon domains: ' . implode( ', ', $diff ),
			);
		} else {
			$this->alerts[] = array(
				'code'    => 0,
				'class'   => 'info',
				'message' => 'WordPress domains are verified to be in sync with Pantheon domains.'
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
				$this->score = $avg;
				$this->action = "You should use object caching";
		}
		$messenger->addMessage(get_object_vars($this));
	}

	public function checkCoreUpdates() {

		ob_start();
		\WP_CLI::run_command(array(
			'core',
			'check-update'
		), array('format' => 'json'));
		$ret = ob_get_clean();
		$updates = !empty($ret) ? json_decode($ret, TRUE) : array();
		$has_minor = $has_major = FALSE;
		foreach ($updates as $update) {
			switch ($update['update_type']) {
				case 'minor':
					$has_minor = TRUE;
					break;
				case 'major':
					$has_major = TRUE;
					break;
			}
		}

		if ( $has_minor ) {
			$this->alerts[] = array(
				'code'  =>  2,
				'class' => 'error',
				'message' => "Updating to WordPress' newest minor version is strongly recommended.",
			);
		} else if ( $has_major ) {
			$this->alerts[] = array(
				'code'    => 1,
				'class'   => 'warning',
				'message' => 'A new major version of WordPress is available for update.'
			);
		} else {
			$this->alerts[]  = array(
				'code'  => 0,
				'class' => 'ok',
				'message' => 'WordPress is at the latest version.',
			);
		}

	}
}
