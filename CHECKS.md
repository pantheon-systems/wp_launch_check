# WP Launch Checks Logic

All the checks in this extension should be explained in detail here. This file should be organized by command and type checker

There are currently two broad types of checkers. 
  * [\Pantheon\Checker](php/pantheon/checker.php): These checks simply examine a piece of data and register and alert if the data exists. For instance, does the ```wp-content/object-cache.php``` exist? If so some object caching is enabled.  
  * [\Pantheon\Filesearcher](php/pantheon/filesearcher.php): These checks are functionally the same as the above except that before being run the class uses [\Symfony\Component\Finder\Finder](http://symfony.com/doc/current/components/finder.html) to load a list of files to be checked and then runs the specified check on each file. The logic is slightly different here to allow the Finder operation to *only* run once even when multiple "Filesearcher" children are running


The Checker oject has two key methods 
  * register( Check $check ): receives an instance of a check to run.
  * execute(): executes all registered checks
  
The checks themselves are all extensions of the [\Patheon\Checkimplementation](php/pantheon/Checkimplemtation.php) class, each containing the following methods: 
  * init()
  * run()
  * message();

The Checker object holds a collection of Check objects which it iterates and invokes each of these methods. In the case of the Filesearcher object, the init() method generates the file list ( if not already present ) and the run() method is passed a $file parameter.

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
      'message' => 'THis is a sample error message',
    );
    ```

## Filesearchers

### Sessions
**Check:** \Pantheon\Checks\Sessions;
This check does a ```preg_match``` on each file passed to the run() method for the regex ```.*(session_start|SESSION).*```

### Secure
**Check:** \Pantheon\Check
