<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;

class Plugins extends Checkimplementation {
  public $check_all_plugins;

  public function __construct($check_all_plugins) {
    $this->check_all_plugins = $check_all_plugins;
  }

  public function init() {
    $this->action = 'No action required';
    $this->description = 'Looking for vulnerable plugins';
    if ( $this->check_all_plugins ) {
      $this->description .= ' ( active and inactive )';
    } else {
      $this->description .= ' ( active only )';
    }
    $this->score = 2;
    $this->result = '';
    $this->label = 'Vulnerable Plugins';
    $this->alerts = array();
    self::$instance = $this;
    return $this;
  }

  public function run() {
    if (!function_exists('get_plugins')) {
      require_once \WP_CLI::get_config('path') . '/wp-admin/includes/plugin.php';
    }
    $all_plugins = get_plugins();
    $update = get_plugin_updates();
    $report = array();
    foreach( $all_plugins as $plugin_path => $data ) {
      $slug = $plugin_path;
      if (stripos($plugin_path,'/')) {
        $slug = substr($plugin_path, 0, stripos($plugin_path,'/'));
      }
      $vulnerable = $this->is_vulnerable($slug, $data['Version']);

      $needs_update = 0;
      $available = '-';
      if (isset($update[$plugin_path])) {
        $needs_update = 1;
        $available = $update[$plugin_path]->update->new_version;
      }

      $report[$slug] = array(
        'slug' => $slug,
        'installed' => (string) $data['Version'],
        'available' => (string) $available,
        'needs_update' => (string) $needs_update,
        'vulnerable'  => is_array( $vulnerable ) ?  "'".$vulnerable['url'][0]."'" : "none",
      );
    }
    $this->alerts = $report;
  }

  /**
  * Checks the plugin slug against the vulnerability db
  * @param $plugin_slug string (required) string representing the plugin slug
  *
  * @return array containing the vulnerability or false ... 'unknown' if couldn't be verified
  */
  public function is_vulnerable($plugin_slug, $current_version) {
    $url = sprintf('https://wpvulndb.com/api/v1/plugins/%s', $plugin_slug);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Pantheon WP LaunchCheck');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $response = curl_exec($ch);
    if ( '404' == curl_getinfo( $ch, CURLINFO_HTTP_CODE ) ) {
      return false;
    }

    $data = json_decode(trim($response));
    if (!$data) return false;
    foreach ($data->plugin->vulnerabilities as $vulnerability) {
      // if the plugin hasn't been fixed then there's still and issue
      if (!isset($vulnerability->fixed_in))
        return (array) $vulnerability;
      // if fixed but in a version greater than installed, still vulnerable
      if (version_compare($vulnerability->fix_in,$current_version,'>'))
        return (array) $vulnerability;
    }

    return false;
  }

  public function message(Messenger $messenger) {
      if (!empty($this->alerts)) {
        $table = new \cli\Table();
        $table->setHeaders(array(
          'slug'=>"Plugin",
          'installed'=>"Current",
          'available' => "Available",
          'needs_update'=>"Needs Update",
          'vulnerable'=>"Vulnerabilities"
        ));
        $count_update = 0;
        $count_vuln = 0;
        foreach( $this->alerts as $alert ) {
          if ($alert['needs_update']) {
            $count_update++;
          }
          if ('none' !== $alert['vulnerable']) {
            $count_vuln++;
          }
          $table->addRow($alert);
        }
        $rendered = PHP_EOL;
        $rendered .= sprintf("Found %d plugins needing updates and %d known vulnerabilities ... \n".PHP_EOL, $count_update, $count_vuln);
        $rendered .= join("\n", $table->getDisplayLines() );
        $this->result .= $rendered;
        if ($count_update > 0) {
          $this->score = 0;
          $this->action = "You should update all out-of-date plugins";
        }

        if ($count_vuln > 0) {
          $this->score = -1;
          $this->action = "Update plugins to fix vulnerabilities";
        }
    }
    $messenger->addMessage(get_object_vars($this));
  }
}
