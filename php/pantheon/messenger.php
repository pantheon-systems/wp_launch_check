<?php
namespace Pantheon;

class Messenger {
	public $messages = array();
	static $instance;

	public function __construct() {
		self::$instance = $this;
		return $this;
	}

	public static function instance() {
		if (self::$instance)
			return self::$instance;
		return new Messenger();
	}

	public static function queue($message) {
			$messenger = self::instance();
			// @todo this changes the check object to an array, better would be to
			// create a message object that could behave as an array when necessary
			$messenger->addMessage($message);
	}

	public function addMessage($message) {
		return array_push($this->messages, $message);
	}

	/**
	* Emit the message in specified format
	*
	* @params $format string optional - options are "raw","json"
	*/
	public static function emit($format='raw') {
		$messenger = self::instance();
		switch($format) {
			case 'pantheon':
			case 'json':
				$formatted = array();
				foreach($messenger->messages as $message) {
						$formatted[$message['name']] = $message;
				}
				\WP_CLI::print_value($formatted,array('format'=>'json'));
				break;
			case 'raw':
			case 'default':
				foreach ($messenger->messages as $message) {
					// colorize
					if ( $message['score'] == 2 ) {
						$color = "%G";
					} elseif ( $message['score'] == 0 ) {
						$color = "%C";
					} else {
						$color = "%R";
					}

					// @todo might be a better way to do this
					echo \cli\Colors::colorize( sprintf(str_repeat('-',80).PHP_EOL."%s: (%s) \n%s\nResult:%s %s\n%s\n\n".PHP_EOL,
						strtoupper($message['label']),
						$message['description'],
						str_repeat('-',80),
						$color,
						$message['result'].'%n', // ugly
						// Check for a recommended action before printing something.
						isset( $message['action'] ) ?? sprintf( "Recommendation: %s", $message['action'] ) )
					);
				}
				break;
		}
	}
}
