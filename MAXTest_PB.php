<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes

require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
require_once 'automationLibrary.php';
require_once 'MAX_LoginLogout.php';
require_once "MAX_API_Get.php";
require_once "MAX_Users.php";

// : End

/**
 * MAXTest_User_Create.php
 *
 * @package MAXTest_User_Create
 * @author Clinton Wright <cwright@bwtrans.com>
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
class MAXTest_User_Create extends PHPUnit_Framework_TestCase
{
    // : Constants
    const DS = DIRECTORY_SEPARATOR;

    const LIVE_URL = "https://login.max.bwtsgroup.com";

    const TEST_URL = "http://max.mobilize.biz";

    const INI_FILE = "user_data.ini";

    const PB_URL = "/Planningboard";

    const FILE_NOT_FOUND = "ERROR: File not found. Please check the path and that the file exists and try again: %s";

    const LOGIN_FAIL = "ERROR: Log into %h was unsuccessful. Please see the following error message relating to the problem: %s";

    const DB_ERROR = "ERROR: There was a problem connecting to the database. See error message: %s";

    const DIR_NOT_FOUND = "The specified directory was not found: %s";

    const ADMIN_URL = "/adminTop?&tab_id=120";

    const NOT_CORRECT_ACTION = "Could not verify the action was correct when updating the action update for: %s";

    const ERROR_NO_GROUPS = "No groups value was given for the user that is been requested to be created and groups are required.";
    
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

    protected $_data = array();

    protected $_datadir;

    protected $_errdir;

    protected $_scrdir;

    protected $_errors = array();

    protected $_tmp;

    protected $_version;
    
    // : Public Functions
    // : Accessors
    // : End
    
    // : Magic
    /**
     * MAXTest_User_Create::__construct()
     * Class constructor
     */
    public function __construct()
    {
        $ini = dirname(realpath(__FILE__)) . self::DS . "ini" . self::DS . self::INI_FILE;
        
        if (is_file($ini) === FALSE) {
            echo "No " . self::INI_FILE . " file found. Please create it and populate it with the following data: username=x@y.com, password=`your password`, your name shown on MAX the welcome page welcome=`Joe Soap` and mode=`test` or `live`" . PHP_EOL;
            return FALSE;
        }
        $data = parse_ini_file($ini);
        if ((array_key_exists("version", $data) && $data["version"]) && (array_key_exists("apiuserpwd", $data) && $data["apiuserpwd"]) && (array_key_exists("datadir", $data) && $data["datadir"]) && (array_key_exists("screenshotdir", $data) && $data["screenshotdir"]) && (array_key_exists("errordir", $data) && $data["errordir"]) && (array_key_exists("username", $data) && $data["username"]) && (array_key_exists("password", $data) && $data["password"]) && (array_key_exists("welcome", $data) && $data["welcome"]) && (array_key_exists("mode", $data) && $data["mode"]) && (array_key_exists("wdport", $data) && $data["wdport"]) && (array_key_exists("browser", $data) && $data["browser"])) {
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
            $this->_version = $data["version"];
            $this->_csv = $data["file1"];
            
            // Determine MAX URL to be used for this test run
            $this->_maxurl = automationLibrary::getMAXURL($this->_mode, $this->_version);
        } else {
            echo "The correct data is not present in user_data.ini. Please confirm. Fields are username, password, welcome and mode" . PHP_EOL;
            return FALSE;
        }
    }

    /**
     * MAXTest_User_Create::__destruct()
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
     * MAXTest_User_Create::testMAXUserCreate
     * This is a function description for a selenium test function
     */
    public function testMAXUserCreate()
    {
        // Main Try - Catch
        try {
            // Initialize session
            $session = $this->_session;
            $this->_session->setPageLoadTimeout(60);
            $w = new PHPWebDriver_WebDriverWait($session, 30);
            
            $_autoLib = new automationLibrary($this->_session, $this, $w, $this->_mode, $this->_version);
            
            // : Load CSV File data
            
            $_file = $this->_datadir . self::DS . $this->_csv;
            
            if ($_autoLib->getDataFromCSVFile($_file) !== FALSE) {
                $this->_data = $_autoLib->getReturnedData();
            } else {
                throw new Exception($_autoLib->getLastError());
            }
            
            // : End
            if ($this->_data && is_array($this->_data)) {
                
                // Sub Try - Catch (1)
                try {
                    
                    $_maxLoginLogout = new maxLoginLogout($_autoLib, $this->_maxurl);
                    
                    // : Log into MAX
                    if (! $_maxLoginLogout->maxLogin($this->_username, $this->_password, $this->_welcome, $this->_version)) {
                        throw new Exception($_maxLoginLogout->getLastError());
                    }
                    // : End

                    $e = $w->until(function ($session)
                    {
                        return $session->element("xpath", "//a[@id='Planning' and @class='dropdown-toggle ng-binding ng-scope' and contains(text(),'Planning')]");
                    });

                    $_autoLib->assertElementPresent("xpath", "//a[@id='Planning' and @class='dropdown-toggle ng-binding ng-scope' and contains(text(),'Planning')]");
                    $this->_session->element("xpath", "//a[@id='Planning' and @class='dropdown-toggle ng-binding ng-scope' and contains(text(),'Planning')]")->click();

                    $e = $w->until(function ($session)
                    {
                        return $session->element("xpath", "//li[@class='dropdown ng-scope open']/div[@class='dropdown-menu']/div[@class='sub-menu ng-scope']/a[@id='Planning_Board']");
                    });

                    $_autoLib->assertElementPresent("xpath", "//a[@id='Planning_Board' and @class='ng-binding ng-scope']");
                    $this->_session->element("xpath", "//a[@id='Planning_Board' and @class='ng-binding ng-scope']")->click();
                    
                    $e = $w->until(function ($session)
                    {
                        return $session->element("xpath", "//select[@ng-model='fleet' and @ng-change='getFleet()' and @class='ng-pristine ng-valid']");
                    });

                    /*$e = $w->until(function ($session)
                    {
                        return $session->element("xpath", "//select[@ng-model='fleet' and @ng-change='getFleet()' and @class='ng-pristine ng-valid']/option[text()='Wilmar Bulk Fleet']");
                    });*/
                    
                    //$this->_session->element("xpath", "//select[@ng-model='fleet' and @ng-change='getFleet()' and @class='ng-pristine ng-valid']/option[text()='Wilmar Bulk Fleet']")->click();

                    $e = $w->until(function ($session)
                    {
                        //return $session->element("xpath", "//*[contains(@id,'truck_id=842')]/div[@class='planningBoardDay']/a[@class='refuelling' and text()='F']");
                        return $session->element("xpath", "//div[@class='planningBoardTruck ng-scope' and contains(@id,'id=842')]/div[@class='planningBoardDay']/a[@ng-click='showRefuel(truck)' and text()='F']");
                    });
                    
                    //$_autoLib->assertElementPresent("xpath", "//*[contains(@id,'truck_id=615')]/div[@class='planningBoardDay']/a[@class='refuelling' and text()='F']");
                    //$this->_session->element("xpath", "//*[contains(@id,'truck_id=615')]/div[@class='planningBoardDay']/a[@class='refuelling' and text()='F']")->click();
                    $this->_session->element("xpath", "//div[@class='planningBoardTruck ng-scope' and contains(@id,'id=842')]/div[@class='planningBoardDay']/a[@ng-click='showRefuel(truck)' and text()='F']")->click();
                    
                    // : Log out of MAX
                    if (! $_maxLoginLogout->maxLogout($this->_session, $w, $this, $this->_version)) {
                        throw new Exception($_maxLoginLogout->getLastError());
                    }
                    // : End
                } catch (Exception $e) {
                    
                    // : Sub Catch (1)
                    throw new Exception($e->getMessage());
                    // : End
                }
                // : Tear Down
                if (isset($_maxLoginLogout)) {
                    unset($_maxLoginLogout);
                }
                
                if (isset($_autoLib)) {
                    unset($_autoLib);
                }
                
                // Close this session
                $this->_session->close();
                // : End
            }
        } catch (Exception $e) {
            
            // : Main Catch
            $_errmsg = preg_replace("/%h/", $this->_maxurl, self::LOGIN_FAIL);
            $_errmsg = preg_replace("/%s/", $e->getMessage(), $_errmsg);
            throw new Exception($_errmsg);
            unset($_errmsg);
            // : End
        }
        
    }
    
    // : Private Functions
    // : End
}
