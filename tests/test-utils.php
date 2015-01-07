<?php
class UtilsTest extends PHPUnit_Framework_TestCase {
	function testFileSearch() {
		$search = dirname(__FILE__).'/data/';
		$files = \Pantheon\Utils::search_php_files($search,'test');
		$this->assertNotEmpty( $files );
		$file = $files[0];
		$this->assertEquals( 'search.php', $file );
		$files = \Pantheon\Utils::search_php_files($search,'zebras');
		$this->assertEmpty( $files );
	}

	function testRegex() {

	}
}

