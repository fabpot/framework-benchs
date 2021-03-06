<?php
/**
 * 
 * Command to run a Solar test series.
 * 
 * Synopsis
 * ========
 * 
 * `**solar run-tests** [options] [CLASS]`
 * 
 * If `CLASS` is empty, runs all test classes in the test directory, and 
 * recursively descends into subdirectories to run those tests as well.
 * 
 * If `CLASS` is given, runs that test class, and recursively descends into
 * its subdirectory to run tests there as well.
 * 
 * 
 * Options
 * =======
 * 
 * `--config FILE`
 * : Path to the Solar.config.php file.  Default false.
 * 
 * `--dir _arg_`
 * : Directory where the test classes are located.  Default is the current
 *   working directory.
 * 
 * Examples
 * ========
 * 
 * `./script/solar run-tests Test_Class`
 * : runs all methods for the test class and its subclasses
 * 
 * `./script/solar run-tests --only Test_Class`
 * : runs all methods for the one test class (no subclasses)
 * 
 * `./script/solar run-tests Test_Class::testMethod`
 * : runs all methods starting with "testMethod" for the test class and its 
 *   subclasses
 * 
 * `./script/solar run-tests --only Test_Class::testMethod`
 * : runs exactly the one Test_Class::testMethod()
 * 
 * 
 * @category Solar
 * 
 * @package Solar_Cli
 * 
 * @author Paul M. Jones <pmjones@solarphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: RunTests.php 3988 2009-09-04 13:51:51Z pmjones $
 * 
 */
class Solar_Cli_RunTests extends Solar_Cli_Base
{
    /**
     * 
     * Runs the tests for a class, descending into subdirectories unless
     * otherwise specified.
     * 
     * @param string $spec The Test_Class or Test_Class::testMethod to run.
     * 
     * @return void
     * 
     */
    protected function _exec($spec = null)
    {
        if (! $spec) {
            throw $this->_exception('ERR_NEED_TEST_SPEC');
        }
        
        // look for a :: in the class name; if it's there, split into class
        // and method
        $pos = strpos($spec, '::');
        if ($pos) {
            $class  = substr($spec, 0, $pos);
            $method = substr($spec, $pos+2);
        } else {
            $class = $spec;
            $method = null;
        }
        
        // run just the one test?
        $only = (bool) $this->_options['only'];
        
        // look for a test-config file?
        $test_config = null;
        if ($this->_options['test_config']) {
            // find the real path to the test_config file
            $test_config = realpath($this->_options['test_config']);
            if ($test_config === false) {
                throw $this->_exception('ERR_TEST_CONFIG_REALPATH', array(
                    'test_config' => $this->_options['test_config'],
                    'realpath'    => $test_config,
                ));
            }
        }
        
        // set up a test suite object 
        $suite = Solar::factory('Solar_Test_Suite', array(
            'verbose'       => $this->_options['verbose'],
            'test_config'   => $test_config,
            'stop_on_fail'  => $this->_options['stop_on_fail'],
        ));
        
        // run the suite
        $suite->run($class, $method, $only);
    }
}
