<?php
use \Pantheon\Utils;
use \Pantheon\Filesearcher;
use \Pantheon\Messenger;

class SampleTest extends PHPUnit_Framework_TestCase {

	function testFileSearch() {
		// replace this with some actual testing code
		$searcher = new Filesearcher(dirname(__FILE__)."/data");
		$searcher->register( new \Pantheon\Checks\Exploited() );
		$searcher->register( new \Pantheon\Checks\Insecure() );
		$searcher->register( new \Pantheon\Checks\Sessions() );
		$searcher->execute();
		foreach($searcher->callbacks() as $check) {
			$this->assertNotEquals(2,$check->score);
		}
		Messenger::emit();
	}
}
