<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Sessions extends Checkimplementation {
	public $name = 'sessions';

	public function init() {
		$this->description = 'Sessions only work when sessions plugin is enabled';
		$this->score = 0;
		$this->result = '';
		$this->label = 'PHP Sessions';
		$this->has_plugin = class_exists("Pantheon_Sessions");
		// If the plugin was not found, define the recommended action.
		// Otherwise, we don't want to recommend anything, we're all good here.
		$this->action = ! $this->has_plugin ? 'You should ensure that the Native PHP Sessions plugin is installed and activated - https://wordpress.org/plugins/wp-native-php-sessions/' : 'No action required';

		return $this;
	}

	public function run($file) {
		if ($this->has_plugin) return;
		$regex = '.*(session_start|\$_SESSION).*';
		preg_match('#'.$regex.'#s',$file->getContents(), $matches, PREG_OFFSET_CAPTURE );
		if ( $matches ) {
			$matches = Utils::sanitize_data($matches);
			$note = '';
			if (count($matches) > 1) {
				array_shift($matches);
			}
			foreach ($matches as $match) {
				$this->alerts[] = array( 'class' => 'error','data'=>array($file->getRelativePathname(),$match[1] + 1, substr($match[0],0,50)));
			}
		}
		return $this;
	}

	public function message(Messenger $messenger) {
		if (!empty($this->alerts)) {
			$checks = array( array(
						'message' => sprintf( "Found %s files that reference sessions. %s ", count($this->alerts), $this->action ),
						'class'   => 'fail',
			) );
			$this->result .= View::make('checklist', array('rows'=>$checks));
			$this->result .= View::make('table', array('headers'=>array('File','Line','Match'),'rows'=>$this->alerts));
			$this->score = 2;
		} else {
			if ( $this->has_plugin ) {
				$this->result .= 'You are running wp-native-php-sessions plugin.';
			} else {
				$this->result .= 'No files referencing sessions found.';
			}
		}
		$messenger->addMessage(get_object_vars($this));
	}

}
