<?php
use \Pantheon\Utils;
use \Pantheon\Filesearcher;
use \Pantheon\Messenger;

class PluginsTest extends PHPUnit_Framework_TestCase {
  
  public function testPluginVulnerable() {
    $plugin_checker = new \Pantheon\Checks\Plugins(TRUE);
    $this->assertFalse( $plugin_checker->is_vulnerable('ajax-search-lite','3.11') );
    $this->assertArrayHasKey('id', $plugin_checker->is_vulnerable('ajax-search-lite','3.06'));
  }

}
