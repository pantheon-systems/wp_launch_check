# WP Launch Checks Logic

All the checks in this extension should be explained in detail here. This file should be organized by command and type checker

There are currently two broad types of checkers. 
  * [\Pantheon\Checker](php/pantheon/checker.php): These checks simply examine a piece of data and register and alert if the data exists. For instance, does the ```wp-content/object-cache.php``` exist? If so some object caching is enabled.  
  * [\Pantheon\Filesearcher](php/pantheon/filesearcher.php): These checks are functionally the same as the above except that before being run the class uses [\Symfony\Component\Finder\Finder](http://symfony.com/doc/current/components/finder.html) to load a list of files to be checked and then runs the specified check on each file. The logic is slightly different here to allow the Finder operation to *only* run once even when multiple "Filesearcher" children are running


The Checker oject has two key methods 
  * ```register( Check $check )```: receives an instance of a check to run.
  * ```execute()```: executes all registered checks
  
The checks themselves are all extensions of the [\Patheon\Checkimplementation](php/pantheon/Checkimplemtation.php) class, each containing the following methods: 
  * ```init()```
  * ```run()```
  * ```message()```;

The Checker object holds a collection of Check objects which it iterates and invokes each of these methods. In the case of the Filesearcher object, the ```init()``` method generates the file list ( if not already present ) and the ```run()``` method is passed a $file parameter.

The message method recieves a [\Pantheon\Messsenger](php/pantheon/messenger.php) and updates the various Check object properties for output. The output of each check is simply the formatted representation of the object properties. 

**Check Obect Properties:**
  * ```$name```: machine name of the check for use at the index of the returned JSON ( if json is specified )
  * ```$description```: textual description of what the check does
  * ```$label```: display version of check name used on dashboard
  * ```$score```: used to toggle display mechanisms in the dashboard
    0: ok (green)
    1: warning (orange)
    2: error (red)
  * ```$result```: rendered html returned for use on the dashboard ( @TODO this should eventual return raw output as well when dashboard is not the intended client )
  * ```$alerts```: an array of alerts to rendered for the ```$result```. Each alert should be an array: ``` array(
      'code' => 2,
      'class' => 'error',
      'message' => 'This is a sample error message',
    );```

## Filesearchers

### Sessions
**Check:** \Pantheon\Checks\Sessions;
This check does a ```preg_match``` on each file passed to the run() method for the regex ```.*(session_start|SESSION).*```

### Secure
**Check:** [\Pantheon\Check\Insecure](php/pantheon/checks/insecure.php)
This check looks for insecure code by running ````preg_match("#.*(eval|base64_decode)\(.*#:", $filecontent)```. This regex can be improved but the theory here is that ```eval``` and ```base64_decode``` are insecure because the first is discouraged even by PHP because it executes arbitrary code. The second isn't necessarily insecure by itself but is often combined with eploits to obfuscate the malicious code. ```base64_decode``` can also sometimes lead to php segfaults [ **This check is not currently used in the Pantheon dashboard ** ]

**Check:** [\Pantheon\Check\Exploited](php/pantheon/checks/exploited.php) This check attempts to find actual exploits by running ```'.*eval\(.*base64_decode\(.*';```. The goal here is to find instance of ```eval``` operating on decoded base64, which is almost certainly a bad idea. This regex should be refined because now it technically could alert when it finds the two functions on the same page but not necessary in the right order, leading to a false positive.

## Regular Checkers 

### General 
**Check:** [\Pantheon\Checks\General](php/pantheon/checks/general.php)
This check does the following:
 * Checks for WP_DEBUG=True, returns 'ok' if in dev, 'warning; in live
 * Checks whether the debug-bar plugin is active, 'ok' in dev, 'warning' in live
 * Counts active plugins. Alerts if more than 100 are active
 * Checks database settings for ```home``` and ```siteurl``` and whether they match. If they do not it recommends fixing. You can do this with WP_CLI/Terminus using 'terminus wp search-replace 'domain1' 'domain2' --site=sitename --env=dev'
 * Checks whether WP Super Cache and/or W3 Total Cache are found and alerts 'warning' if so.
 * 

### Database
**Database:** [\Pantheon\Checks\Database](php/pantheon/checks/database.php) 
This check runs the following db checks
 * Runs this query ```SELECT TABLES.TABLE_NAME, TABLES.TABLE_SCHEMA, TABLES.TABLE_ROWS, TABLES.DATA_LENGTH, TABLES.ENGINE from information_schema.TABLES where TABLES.TABLE_SCHEMA = '%s'``` and checks that all tables as set to InnoDb storage engine, alerts 'error' if not and specifies a query that can be run to fix the issue.
 * Also checks number of rows in the options table. If over 10,000 it alerts 'error' because this is an indication that expired transients are stacking up or that they are using a lugin that over uses the options table. A bloated options table can be a major cause of WP performance issues. 
 * Counts options that are set to 'autoload', alerts is more than 1,000 are found. This is relevant because WordPress runs ```SELECT * FROM wp_options WHERE autoload = 'yes'``` on every page load to prepopulate the runtime cache. In cases where the query takes to long or returns too much data this can slow down page load. The only benefit to the runtime cache comes when object caching is not in use, but it is strongly encourage that some kind of object cache is always in use. 
 * Looks for transients and expired transients. Some plugins will use transients regularly but not add a garbage collection cron task. Core WordPress has not garbage collection for the transient api. Over time this can cause transients to bloat the ```wp_options``` database as mentioned above.

### Cron
**Cron:** [\Pantheon\Checks\Cron](php/commands/checks/cron.php)
This check simple examines whether ```DISABLE_WP_CRON``` evaluates ```true``` to see if cron has been disabled. ( We should probably also curl the wp-cron.php?doing_wp_cron and ensure we get a 200 ). Some hosts disable the default WP_Cron functionality, substituting a system cron, because the HTTP base WP_Cron can sometimes have race conditions develop causing what might be referred to as "runaway cron", in which HTTP multiple requests trigger the cron a small amount of time causing a spike in PHP/MySQL resource consumption. This check also dumps the scheduled tasks into a table using ```get_option('cron')```. 

### object-cache
**objectcache** [\Pantheon\Checks\Cron](php/commands/checks/objectcache.php)
Checks is the ```wp-content/object-cache.php``` exists to detemine whether object caching is in use. Checks that the ```global $redis_server``` variable is not empty to determine whether redis is being used.

### plugins
**plugins** [\Pantheon\Checks\Plugins](php/commands/checks/plugins.php)
Checks all plugins against the wpvulndb.com database we license. Alerts 'error' if a vulnerability is found and links to the wpvulndb.com page for more info. Also checks for available updates and alerts 'warning' if plugins needing an update are found.
