<?php
class UtilsTest extends PHPUnit_Framework_TestCase {
	private $searchdir;

	public function __construct() {
		$this->searchdir = dirname(__FILE__).'/data/';
	}

	function testFileSearch() {
		$files = \Pantheon\Utils::search_php_files($this->searchdir,'test');
		$this->assertNotEmpty( $files );
		$file = $files[0];
		$this->assertEquals( 'search.php', $file );
		$files = \Pantheon\Utils::search_php_files($this->searchdir,'zebras');
		$this->assertEmpty( $files );
	}

	function testRegexs() {
		$regex = '.*(eval|base64_decode)\(.*';
		$files = \Pantheon\Utils::search_php_files($this->searchdir, $regex);
		$this->assertNotEmpty($files);
		$this->assertEquals(4, count($files));

		$regex = '.*eval\(.*base64_decode\(.*';
		$files = \Pantheon\Utils::search_php_files($this->searchdir, $regex);
		$this->assertNotEmpty($files);
		$this->assertContains('exploited.php',$files);
		$this->assertEquals(2, count($files));
	}

}
