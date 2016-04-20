<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes

require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
require_once dirname(__FILE__) . '/get_users_bu_from_list.php';

// : End

/**
 * web_driver_template_for_max_tests.php
 *
 * @package web_driver_template_for_max_tests
 * @author Clinton Wright <cwright@bwtsgroup.com>
 * @copyright 2013 onwards Barloworld Transport (Pty) Ltd
 * @license GNU GPL
 * @link http://www.gnu.org/licenses/gpl.html
 *       * This program is free software: you can redistribute it and/or modify
 *       it under the terms of the GNU General Public License as published by
 *       the Free Software Foundation, either version 3 of the License, or
 *       (at your option) any later version.
 *      
 *       This program is distributed in the hope that it will be useful,
 *       but WITHOUT ANY WARRANTY; without even the implied warranty of
 *       MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *       GNU General Public License for more details.
 *      
 *       You should have received a copy of the GNU General Public License
 *       along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
class web_driver_template_for_max_tests extends PHPUnit_Framework_TestCase
{
    // : Constants
    const DS = DIRECTORY_SEPARATOR;

    const LIVE_URL = "https://login.max.bwtsgroup.com";

    const TEST_URL = "http://max.mobilize.biz";

    const INI_FILE = "user_data.ini";

    const PB_URL = "/Planningboard";

    const USER_PERSONAL_GROUP = "/DataBrowser?browsePrimaryObject=324&browsePrimaryInstance=";
    
    // : Variables
    protected static $driver;

    protected $_maxurl;

    protected $_mode;

    protected $_username;

    protected $_password;

    protected $_welcome;

    protected $_wdport;

    protected $_proxyip;

    protected $_browser;

    protected $_file1;

    protected $_file2;

    protected $_datadir;

    protected $_errdir;

    protected $_scrdir;

    protected $_tmpVar;

    protected $_errors = array();
    
    // : Public Functions
    // : Accessors
    // : End
    
    // : Magic
    /**
     * web_driver_template_for_max_tests::__construct()
     * Class constructor
     */
    public function __construct()
    {
        $ini = dirname(realpath(__FILE__)) . self::DS . "/ini" . self::DS . self::INI_FILE;
        
        if (is_file($ini) === FALSE) {
            echo "No " . self::INI_FILE . " file found. Please create it and populate it with the following data: username=x@y.com, password=`your password`, your name shown on MAX the welcome page welcome=`Joe Soap` and mode=`test` or `live`" . PHP_EOL;
            return FALSE;
        }
        $data = parse_ini_file($ini);
        if ((array_key_exists("datadir", $data) && $data["datadir"]) && (array_key_exists("screenshotdir", $data) && $data["screenshotdir"]) && (array_key_exists("errordir", $data) && $data["errordir"]) && (array_key_exists("file1", $data) && $data["file1"]) && (array_key_exists("file2", $data) && $data["file2"]) && (array_key_exists("username", $data) && $data["username"]) && (array_key_exists("password", $data) && $data["password"]) && (array_key_exists("welcome", $data) && $data["welcome"]) && (array_key_exists("mode", $data) && $data["mode"]) && (array_key_exists("wdport", $data) && $data["wdport"]) && (array_key_exists("proxy", $data) && $data["proxy"]) && (array_key_exists("browser", $data) && $data["browser"])) {
            $this->_username = $data["username"];
            $this->_password = $data["password"];
            $this->_welcome = $data["welcome"];
            $this->_mode = $data["mode"];
            $this->_wdport = $data["wdport"];
            $this->_proxyip = $data["proxy"];
            $this->_browser = $data["browser"];
            $this->_datadir = $data["datadir"];
            $this->_scrdir = $data["screenshotdir"];
            $this->_errdir = $data["errordir"];
            $this->_file1 = $data["file1"];
            $this->_file2 = $data["file2"];
            switch ($this->_mode) {
                case "live":
                    $this->_maxurl = self::LIVE_URL;
                    break;
                default:
                    $this->_maxurl = self::TEST_URL;
            }
        } else {
            echo "The correct data is not present in user_data.ini. Please confirm. Fields are username, password, welcome and mode" . PHP_EOL;
            return FALSE;
        }
    }

    /**
     * web_driver_template_for_max_tests::__destruct()
     * Class destructor
     * Allow for garbage collection
     */
    public function __destruct()
    {
        unset($this);
    }
    // : End
    public function setUp()
    {
        // This would be the url of the host running the server-standalone.jar
        $wd_host = "http://localhost:$this->_wdport/wd/hub";
        self::$driver = new PHPWebDriver_WebDriver($wd_host);
        if (! $this->_proxyip) {
            $this->_session = self::$driver->session($this->_browser);
        } else {
            $desired_capabilities = array();
            $proxy = new PHPWebDriver_WebDriverProxy();
            $proxy->httpProxy = $this->_proxyip;
            $proxy->add_to_capabilities($desired_capabilities);
            $this->_session = self::$driver->session($this->_browser, $desired_capabilities);
        }
    }

    /**
     * web_driver_template_for_max_tests::testFunctionTemplate
     * This is a function description for a selenium test function
     */
    public function testAddBUToUsers()
    {
        // Create new object for included class to import data
        $_file1 = $this->_datadir . self::DS . $this->_file1;
        $_file2 = $this->_datadir . self::DS . $this->_file2;
        
        if (file_exists($_file1) && file_exists($_file2)) {
            $_users_bu_script = new get_users_bu_from_list($_file1, $_file2);
            // Get the user data list
            $_data = $_users_bu_script->getData();
            // Get the predefined list of Buiness Units
            $_bu = $_users_bu_script->getBUList();
            // Get all users that were not found in the staff employee list
            $_nomatch = $_users_bu_script->getNoMatch();
            $_users_bu_script->exportData("users_data.csv");
            $_users_bu_script->exportNoMatch("users_not_found.csv");
            
            if ($_data) {
                // Initialize session
                $session = $this->_session;
                $this->_session->setPageLoadTimeout(60);
                $w = new PHPWebDriver_WebDriverWait($session);
                
                try {
                    
                    // Load MAX home page
                    $this->_session->open($this->_maxurl);
                    
                    // : Wait for page to load and for elements to be present on page
                    $e = $w->until(function ($session) {
                        return $session->element('css selector', "#contentFrame");
                    });
                    
                    $iframe = $this->_session->element('css selector', '#contentFrame');
                    $this->_session->switch_to_frame($iframe);
                    
                    $e = $w->until(function ($session) {
                        return $session->element('css selector', 'input[id=identification]');
                    });
                    // : End
                    
                    // : Assert element present
                    $this->assertElementPresent('css selector', 'input[id=identification]');
                    $this->assertElementPresent('css selector', 'input[id=password]');
                    $this->assertElementPresent('css selector', 'input[name=submit][type=submit]');
                    // : End
                    
                    // Send keys to input text box
                    $e = $this->_session->element('css selector', 'input[id=identification]')->sendKeys($this->_username);
                    // Send keys to input text box
                    $e = $this->_session->element('css selector', 'input[id=password]')->sendKeys($this->_password);
                    
                    // Click login button
                    $this->_session->element('css selector', 'input[name=submit][type=submit]')->click();
                    // Switch out of frame
                    $this->_session->switch_to_frame();
                    
                    // : Wait for page to load and for elements to be present on page
                    $e = $w->until(function ($session) {
                        return $session->element('css selector', "#contentFrame");
                    });
                    $iframe = $this->_session->element('css selector', '#contentFrame');
                    $this->_session->switch_to_frame($iframe);
                    $e = $w->until(function ($session) {
                        return $session->element("xpath", "//*[text()='" . $this->_welcome . "']");
                    });
                    $this->assertElementPresent("xpath", "//*[text()='" . $this->_welcome . "']");
                    // Switch out of frame
                    $this->_session->switch_to_frame();
                } catch (Exception $e) {
                    throw new Exception("ERROR: Failed to log into MAX." . PHP_EOL . $e->getMessage());
                }
                
                // : Load Planningboard to rid of iframe loading on every page from here on
                $this->_session->open($this->_maxurl . self::PB_URL);
                $e = $w->until(function ($session) {
                    return $session->element("xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]");
                });
                // : End
                
                // : Main Loop
                foreach ($_data as $key => $value) {
                    try {
                        $this->_session->open($this->_maxurl . self::USER_PERSONAL_GROUP . $value["personalgroupid"]);
                        $this->_tmpVar = "{$value["firstnames"]} {$value["surname"]}";
                        $e = $w->until(function ($session) {
                            return $session->element("xpath", "//div[contains(text(),'{$this->_tmpVar}')]");
                        });
                        
                        foreach ($value["bugroups"] as $_bugroup) {
                            try {
                                $this->assertElementPresent('css selector', 'div#button-create');
                                $this->_session->element('css selector', 'div#button-create')->click();
                                
                                $e = $w->until(function ($session) {
                                    return $session->element("xpath", "//*[contains(text(),'Add a member to a group')]");
                                });
                                
                                $this->assertElementPresent("xpath", "//*[@name='Group_Role_Link[0][played_by_group_id]']");
                                $this->assertElementPresent("xpath", "//*[@name='Group_Role_Link[0][group_id]']");
                                $this->assertElementPresent("xpath", "//*[@name='Group_Role_Link[0][role_id]']");
                                $this->assertElementPresent("css selector", "input[name=save][type=submit]");
                                
                                $this->_session->element("xpath", "//*[@name='Group_Role_Link[0][group_id]']/option[contains(text(), '{$_bu[$_bugroup]}')]")->click();
                                $this->_session->element("css selector", "input[name=save][type=submit]")->click();
                                
                                $e = $w->until(function ($session) {
                                    return $session->element("xpath", "//div[contains(text(),'{$this->_tmpVar}')]");
                                });
                                
                                $this->assertElementPresent("xpath", "//a/nobr[contains(text(), '{$_bu[$_bugroup]}')]");
                            } catch (Exception $e) {
                                $_err_cnt = count($this->_errors);
                                $this->_errors[$_err_cnt + 1]["firstnames"] = $value["firstnames"];
                                $this->_errors[$_err_cnt + 1]["surname"] = $value["surname"];
                                $this->_errors[$_err_cnt + 1]["bu_group"] = $_bu[$_bugroup];
                                $this->_errors[$_err_cnt + 1]["err_msg"] = $e->getMessage();
                                $this->takeScreenshot($this->_session);
                            }
                        }
                    } catch (Exception $e) {
                        $_err_cnt = count($this->_errors);
                        $this->_errors[$_err_cnt + 1]["firstnames"] = $value["firstnames"];
                        $this->_errors[$_err_cnt + 1]["surname"] = $value["surname"];
                        $this->_errors[$_err_cnt + 1]["bu_group"] = "";
                        $this->_errors[$_err_cnt + 1]["err_msg"] = $e->getMessage();
                        $this->takeScreenshot($this->_session);
                    }
                    // : End
                }
                // : Report errors if any occured
                if ($this->_errors) {
                    $_errfile = date("Y-m-d H:i:s") . "_error_report_addbutousers_script.csv";
                    $this->exportToCSV($_errfile, $this->_errors);
                    echo "Exported error report to the following path and file: " . dirname(__FILE__) . $_errfile;
                }
                // : End
                
                // : Tear Down
                // Click the logout link
                $this->_session->element('xpath', "//*[contains(@href,'/logout')]")->click();
                // Wait for page to load and for elements to be present on page
                $e = $w->until(function ($session) {
                    return $session->element('css selector', 'input[id=identification]');
                });
                $this->assertElementPresent('css selector', 'input[id=identification]');
                // Terminate session
                $this->_session->close();
                // : End
            } else {
                throw new Exception("ERROR: No data imported using get_users_bu_from_list script. Quitting...");
            }
        } else {
            throw new Exception("ERROR: File1 and/or File2 not found given in the user_data.ini config file for this script. Please check that these files exist.");
        }
    }
    
    // : Private Functions
    
    /**
     * web_driver_template_for_max_tests::takeScreenshot($_session)
     * This is a function description for a selenium test function
     *
     * @param object: $_session            
     */
    private function takeScreenshot($_session)
    {
        $_img = $_session->screenshot();
        $_data = base64_decode($_img);
        $_file = dirname(__FILE__) . self::DS . date("Y-m-d_His") . "_WebDriver.png";
        $_success = file_put_contents($_file, $_data);
        if ($_success) {
            return $_file;
        } else {
            return FALSE;
        }
    }

    /**
     * web_driver_template_for_max_tests::assertElementPresent($_using, $_value)
     * This is a function description for a selenium test function
     *
     * @param string: $_using            
     * @param string: $_value            
     */
    private function assertElementPresent($_using, $_value)
    {
        $e = $this->_session->element($_using, $_value);
        $this->assertEquals(count($e), 1);
    }

    /**
     * web_driver_template_for_max_tests::exportToCSV($csvFile, $arr)
     * From supplied csv file save data into multidimensional array
     *
     * @param string: $csvFile            
     * @param array: $_arr            
     */
    private function exportToCSV($csvFile, $_arr)
    {
        try {
            $_data = (array) array();
            if (file_exists(dirname($csvFile))) {
                $_handle = fopen($csvFile, 'w');
                foreach ($_arr as $key => $value) {
                    fputcsv($_handle, $value);
                }
                fclose($_handle);
            } else {
                $_msg = preg_replace("@%s@", $csvFile, self::DIR_NOT_FOUND);
                throw new Exception($_msg);
            }
        } catch (Exception $e) {
            return FALSE;
        }
    }
    
    // : End
}