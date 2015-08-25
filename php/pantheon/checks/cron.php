<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Cron extends Checkimplementation {
  const MAX_CRON_DISPLAY = 50;

  public function init() {
    $this->name = 'cron';
    $this->action = 'No action required';
    $this->description = 'Checking whether cron is enabled and what jobs are scheduled';
    $this->score = 0;
    $this->result = '';
    $this->label = 'Cron';
    $this->alerts = array();
    self::$instance = $this;
    return $this;
  }

  public function run() {
    global $redis_server;
    $this->checkIsRegularCron();
    $this->checkCron();
    return $this;
  }

  public function checkIsRegularCron() {
    if ( defined("DISABLE_WP_CRON") and true == DISABLE_WP_CRON ) {
      $this->alerts[] = array( 
        'class' => 'fail', 
        'message' => 'Cron appears to be disabled, make sure DISABLE_WP_CRON is not defined in your wp-config.php', 
        'code' => 2 
      );
    } else {
      $this->alerts[] = array(
        'class' => 'pass',
        'message' => 'Cron is enabled.',
        'code' => 0,
      );
    }
  }

  public function checkCron() {
    $total = 0;
    $invalid = 0;
    $overdue = 0;
    $now = time();

    $this->cron_rows = array();
    $cron = get_option('cron');

    // Count the cron jobs and alert if there are an excessive number scheduled.
    foreach ($cron as $timestamp => $crons) { 
      foreach ($crons as $job => $data) {
        $class = 'ok';
        $data = array_shift($data);

        // If this is an invalid timestamp.
        if (!is_int($timestamp) || $timestamp == 0) {
          $invalid++;
          $class = "error";
          $next = 'INVALID';
        }
        // If the timestamp is in the past.
        else if ($timestamp < $now) {
          $past++;
          $class = "error";
          $next = date('M j,Y @ H:i:s', $timestamp) . ' (PAST DUE)';
        }
        $this->cron_rows[] = array('class' => $class, 'data' => array('jobname' => $job, 'schedule' => $data['schedule'], 'next' => $next));
        $total++;
      }
    }

    if ($invalid) {
      $this->alerts[] = array( 
        'class' => 'fail', 
        'message' => "You have $invalid cron job(s) with an invalid time.", 
        'code' => 2
      );
    }    
    if ($overdue) {
      $this->alerts[] = array( 
        'class' => 'pass', 
        'message' => "You have $past cron job(s) which are past due. Make sure that cron jobs are running on your site.", 
        'code' => 1 
      );
    }    
    if ($total > self::MAX_CRON_DISPLAY) {
      $this->alerts[] = array( 
        'class' => 'pass', 
        'message' => "You have $total cron jobs scheduled. This is too many to display and may indicate a problem with your site.", 
        'code' => 1 
      );
      // Truncate the output.
      // @TODO: Put a note next to the output table reiterating that these are not the full results.
      $this->cron_rows = array_splice($this->cron_rows, 0, self::MAX_CRON_DISPLAY);
    }
  }

  public function message(Messenger $messenger) {
    if (!empty($this->alerts)) {
      $total = 0;
      $rows = array();
      foreach ($this->alerts as $alert) {
        $total += $alert['code'];
        $label = 'info';
        if (1 === $alert['code']) {
          $label = 'warning';
        } elseif( 2 >= $alert['code']) {
          $label = 'error';
        }
        $rows[] = array(
          'message' => $alert['message'],
          'class' => $label
        );
      }
      
      $avg = $total/count($this->alerts);
      $this->result = sprintf("%s\n%s", $this->description, View::make('checklist', array('rows' => $rows)));
      
      // format the cron table
      $rows = array();
      if ($this->cron_rows) {
        $headers = array( 
          'jobname' => 'Job',
          'schedule' => 'Frequency',
          'next'    => 'Next Run',
        );
        
        $this->result .= sprintf( "<hr/>%s",View::make('table', array('rows' => $this->cron_rows, 'headers' => $headers)));
        $this->score = $avg;
      }
    }
    $messenger->addMessage(get_object_vars($this));
  }
}
