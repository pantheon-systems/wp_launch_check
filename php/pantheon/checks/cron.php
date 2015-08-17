<?php
namespace Pantheon\Checks;

use Pantheon\Utils;
use Pantheon\Checkimplementation;
use Pantheon\Messenger;
use Pantheon\View;

class Cron extends Checkimplementation {
 
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
    $this->cron = get_option('cron'); 
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
        $this->result = sprintf("%s\n%s", $this->description, View::make('checklist', array('rows'=> $rows)));
        // format the cron table
        $rows = array();
        $headers = array( 
          'jobname' => 'Job',
          'schedule' => 'Frequency',
          'next'    => 'Next Run',
        );

        // @TODO move this logic to the run() function or checkCron() function
				if ($this->cron) {
        	foreach ($this->cron as $timestamp => $crons) { 
          	foreach ($crons as $job => $data) {
	            $class = 'ok';
  	          $data = array_shift($data);
    	        if ($timestamp < time()) {
        	      $class = "error";
      	        $this->action = "<div class='warning'>Some cronjobs are outdated.</div>";
          	  }
            	$rows[] = array('class'=>$class,'data'=> array('jobname' => $job,'schedule' => $data['schedule'], 'next' => date('M j, Y @ H:i:s', $timestamp)));    
          	}
        	}
				}
        $this->result .= sprintf( "<hr/>%s",View::make('table', array('rows'=>$rows,'headers'=>$headers)));
        $this->score = $avg;
    }
    $messenger->addMessage(get_object_vars($this));
  }
}
