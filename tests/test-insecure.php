<?php
use \Symfony\Component\Finder\SplFileInfo;

class InsecureTest extends PHPUnit_Framework_TestCase {

  function testInsecure() {
    $check = new \Pantheon\Checks\Insecure();
    $file = new SplFileInfo(dirname(__FILE__).'/data/insecure-file.php', dirname(__FILE__).'/data/', './insecure-file.php');
    $check->run($file);
    $this->assertEquals($check->alerts[0]['data'][0], './insecure-file.php');
    $this->assertEquals($check->alerts[0]['data'][1], '13');
    $this->assertEquals($check->alerts[0]['data'][2], 'eval(\'\<\? echo "tem";\');');
  }
}
