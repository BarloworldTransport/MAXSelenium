<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes

require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
require_once 'PullDataFromMySQLQuery.php';
require_once 'FileParser.php';

// : End

/**
 * MAXLive_FleetTruckLinkCommander.php
 *
 * @package MAXLive_FleetTruckLinkCommander
 * @author Clinton Wright <cwright@bwtrans.com>
 * @copyright 2013 onwards Barloworld Transport (Pty) Ltd
 * @license GNU GPLv2
 * @link https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class MAXLive_FleetTruckLinkCommander extends PHPUnit_Framework_TestCase
{
    // : Constants
    const DS = DIRECTORY_SEPARATOR;

    const LIVE_URL = "https://login.max.bwtsgroup.com";

    const TEST_URL = "http://max.mobilize.biz";

    const INI_FILE = "user_data.ini";

    const PB_URL = "/Planningboard";

    const TRUCK_URL = "/DataBrowser?browsePrimaryObject=402&browsePrimaryInstance=";

    const FILE_NOT_FOUND = "ERROR: File not found. Please check the path and that the file exists and try again: %s";

    const LOGIN_FAIL = "ERROR: Log into %h was unsuccessful. Please see the following error message relating to the problem: %s";

    const DB_ERROR = "ERROR: There was a problem connecting to the database. See error message: %s";

    const DIR_NOT_FOUND = "The specified directory was not found: %s";

    const ADMIN_URL = "/adminTop?&tab_id=120";

    const NOT_CORRECT_ACTION = "Could not verify the action was correct when updating the action update for: %s";

    const FLEET_URL = "/DataBrowser?browsePrimaryObject=508&browsePrimaryInstance=";

    const FLT_URL = "/DataBrowser?browsePrimaryObject=509&browsePrimaryInstance=";
    
    // : Variables
    protected static $driver;

    protected $_maxurl;

    protected $_mode;

    protected $_username;

    protected $_password;

    protected $_welcome;

    protected $_wdport;

    protected $_proxyip;

    protected $_dbhost;

    protected $_browser;

    protected $_data = array();

    protected $_file1;

    protected $_datadir;

    protected $_errdir;

    protected $_scrdir;

    protected $_errors = array();

    protected $_tmp;

    protected $_queries = array(
        "SELECT id, fleetnum from udo_truck where id IN (%d);",
        "SELECT id, name from udo_fleet where id IN (%d);",
        "SELECT ftl.id, ftl.truck_id, ftl.fleet_id FROM udo_fleettrucklink as ftl left join daterangevalue as drv on (drv.objectInstanceId=ftl.id) WHERE (drv.beginDate IS NOT NULL) AND (drv.endDate IS NULL OR drv.endDate >= DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AND ftl.truck_id IN (%e) AND ftl.fleet_id IN (%f);"
    );
    
    // : Public Functions
    // : Accessors
    // : End
    
    // : Magic
    /**
     * MAXLive_FleetTruckLinkCommander::__construct()
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
        if ((array_key_exists("file1", $data) && $data["file1"]) && (array_key_exists("dbhost", $data) && $data["dbhost"]) && (array_key_exists("datadir", $data) && $data["datadir"]) && (array_key_exists("screenshotdir", $data) && $data["screenshotdir"]) && (array_key_exists("errordir", $data) && $data["errordir"]) && (array_key_exists("username", $data) && $data["username"]) && (array_key_exists("password", $data) && $data["password"]) && (array_key_exists("welcome", $data) && $data["welcome"]) && (array_key_exists("mode", $data) && $data["mode"]) && (array_key_exists("wdport", $data) && $data["wdport"]) && (array_key_exists("proxy", $data) && $data["proxy"]) && (array_key_exists("browser", $data) && $data["browser"])) {
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
            $this->_dbhost = $data["dbhost"];
            $this->_file1 = $data["file1"];
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
     * MAXLive_FleetTruckLinkCommander::__destruct()
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
     * MAXLive_FleetTruckLinkCommander::testFleetTruckLinkCommander
     * This is a function description for a selenium test function
     */
    public function testFleetTruckLinkCommander()
    {
        
        // : Import Data
        $_file1 = dirname(__FILE__) . self::DS . $this->_datadir . self::DS . $this->_file1;
        
        if (file_exists($_file1)) {
            $_csvFile = new FileParser($_file1);
            $_csvData = $_csvFile->parseFile();
            if ($_csvData) {
                foreach ($_csvData as $key => $value) {
                    if ($key !== 0) {
                        foreach ($value as $childKey => $childValue) {
                            $this->_data[$key][$_csvData[0][$childKey]] = str_ireplace("'", "", $childValue);
                        }
                    }
                }
            }
        } else {
            $_errmsg = preg_replace("/%s/", $_file1, self::FILE_NOT_FOUND);
            throw new Exception("$_errmsg\n");
        }
        // : Using imported ids from csv file get the string names for the trucks and fleets
        
        $_dbh = new PullDataFromMySQLQuery("max2", $this->_dbhost);
        $_data = (array) array();
        
        if ($this->_data) {
            foreach ($this->_data as $key => $value) {
                // : Split fleet and truck strings into arrays
                $_fleets = $value['fleets'];
                $_trucks = $value['truck_id'];
                // : End
                
                if ($_fleets) {
                    
                    $_query = preg_replace("/%d/", $_fleets, $this->_queries[1]);
                    $_result = $_dbh->getDataFromQuery($_query);
                    
                    if ($_result) {

                        // : Loop each result found
                        foreach ($_result as $rkey => $rvalue) {
                            // : If fleet found in original csv data been looped then add fleet name to main data array;
                            
                            if (stripos($_fleets, strval($rvalue['id'])) !== false) {
                                $_data['fleets'][$rvalue['id']] = $rvalue['name'];
                            }
                            // : End
                            
                            // : End
                        }
                    } else {
                        $_errmsg = "No fleets found for transaction: {$this->_data[$key]['process_id']}, fleet value: $_fleets";
                        $this->reportNewError($_errmsg);
                    }
                    
                    if ($_trucks) {
                        $_query = preg_replace("/%d/", $_trucks, $this->_queries[0]);
                        $_result = $_dbh->getDataFromQuery($_query);
                        if ($_result) {
                            
                            // : Loop each result found
                            foreach ($_result as $rkey => $rvalue) {
                                
                                // : If truck found in original csv data been looped then add fleetnum to main data array
                                if (stripos($_trucks, strval($rvalue['id'])) !== false) {
                                    $_data['trucks'][$rvalue['id']] = $rvalue['fleetnum'];
                                }
                                // : End
                            }
                            // : End
                        }
                        
                        // : Get all active fleettrucklinks for trucks in fleets
                        $_query = preg_replace("/%e/", $_trucks, $this->_queries[2]);
                        $_query = preg_replace("/%f/", $_fleets, $_query);
                        $_result = $_dbh->getDataFromQuery($_query);
                        
                        if ($_result) {
                            
                            foreach ($_result as $rkey => $rvalue) {
                                if (stripos($_fleets, strval($rvalue['fleet_id'])) !== false && stripos($_trucks, strval($rvalue['truck_id'])) !== false) {
                                    $_data['fleettrucklinks'][$rvalue['fleet_id']][$rvalue['truck_id']] = $rvalue['id'];
                                }
                            }
                        }
                        // : End
                    } else {
                        $_errmsg = "No trucks found for transaction: {$this->_data[$key]['process_id']}, truck value: $_trucks";
                        $this->reportNewError($_errmsg);
                    }
                }
            }
        }
        
        $_dbh = null;
        // : End
        
        try {
            // Initialize session
            $session = $this->_session;
            $this->_session->setPageLoadTimeout(15);
            $w = new PHPWebDriver_WebDriverWait($session, 15);
            
            // : Log into MAX
            // Load MAX home page
            $this->_session->open($this->_maxurl);
            
            // : Wait for page to load and for elements to be present on page
            $e = $w->until(function ($session)
            {
                return $session->element('css selector', "#contentFrame");
            });
            
            $iframe = $this->_session->element('css selector', '#contentFrame');
            $this->_session->switch_to_frame($iframe);
            
            $e = $w->until(function ($session)
            {
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
            $e = $w->until(function ($session)
            {
                return $session->element('css selector', "#contentFrame");
            });
            $iframe = $this->_session->element('css selector', '#contentFrame');
            $this->_session->switch_to_frame($iframe);
            $e = $w->until(function ($session)
            {
                return $session->element("xpath", "//*[text()='" . $this->_welcome . "']");
            });
            $this->assertElementPresent("xpath", "//*[text()='" . $this->_welcome . "']");
            // Switch out of frame
            $this->_session->switch_to_frame();
            // : End
            
            // : Load Planningboard to rid of iframe loading on every page from here on
            $this->_session->open($this->_maxurl . self::PB_URL);
            $e = $w->until(function ($session)
            {
                return $session->element("xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]");
            });
            // : End
        } catch (Exception $e) {
            $_errmsg = preg_replace("/%h/", $this->_maxurl, self::LOGIN_FAIL);
            $_errmsg = preg_replace("/%h/", $e->getMessage(), $_errmsg);
            throw new Exception($_errmsg);
            unset($_errmsg);
        }
        
        // : Main Loop
        foreach ($this->_data as $key => $value) {
            try {
                
                // : Split fleet and truck strings into arrays
                $_fleets = explode(',', $value['fleets']);
                $_trucks = explode(',', $value['truck_id']);
                // : End
                
                // : Make 100% sure that the operation is set correctly
                if (stripos($value['operation'], 'create') !== false) {
                    $_operation = "create";
                } else if (stripos($value['operation'], 'update') !== false) {
                    $_operation = "update";
                } else if (stripos($value['operation'], 'remove') !== false) {
                    $_operation = "remove";
                } else {
                    throw new Exception("Was not able to determine the operation to be performed");
                }
                // : End
                
                // : Convert unix time stamp to string formatted Y-m-d H:i:s
                $_start_date = $this->convertUnixToFormattedTime($value['start_date']);
                $_end_date = $this->convertUnixToFormattedTime($value['end_date']);
                //  : End
                
                // : Check if operation and dates required are valid and ready to go else skip record and report error
                if ((($_operation === 'create') && ($_start_date === FALSE)) || (($_operation === 'update') && ($_start_date === FALSE || $_end_date === FALSE)) || (($_operation === 'remove') && ($_end_date === FALSE))) {
                    throw new Exception("Datetime value supplied for operation: $_operation could not be converted or was not supplied.");    
                }
                // : End
                
                // : Loop fleet and truck arrays to process each link operation for each truck in each fleet
                foreach ($_fleets as $_fleet) {
                    
                    if (isset($_data['fleets'][$_fleet])) {
                        $_fleet_name = $_data['fleets'][$_fleet];
                    } else {
                        $_fleet_name = null;
                        $_errmsg = "Could not obtain get name for fleet with ID: $_fleet";
                        $this->reportNewError($_errmsg);
                    }
                    
                    foreach ($_trucks as $_truck) {
                        
                        try {
                            
                            if (isset($_data['trucks'][$_truck])) {
                                $_truck_fleetnum = $_data['trucks'][$_truck];
                            } else {
                                $_truck_fleetnum = null;
                                $_errmsg = "Could not obtain get fleetnum for truck with ID: $_truck";
                                $this->reportNewError($_errmsg);
                            }
                            
                            // : Load Fleet DataBrowser page
                            $this->_session->open($this->_maxurl . self::FLEET_URL . $_fleet);
                            $this->_tmp = $_fleet_name;
                            $e = $w->until(function ($session)
                            {
                                return $session->element("xpath", "//*[@id='toolbar']/div[contains(text(), '{$this->_tmp}')]");
                            });
                            
                            // : Check if fleettrucklink exists for the transaction and store it
                            if (isset($_data['fleettrucklinks'][$_fleet][$_truck])) {
                                if (is_int($_data['fleettrucklinks'][$_fleet][$_truck]) && $_data['fleettrucklinks'][$_fleet][$_truck] !== 0) {
                                    $_ftl_id = $_data['fleettrucklinks'][$_fleet][$_truck];
                                } else {
                                    $_ftl_id = null;
                                }
                            } else {
                                $_ftl_id = null;
                            }
                            // : End
                            
                            // : Run automation depending on which operation has been set
                            if ($_ftl_id !== null) {
                                
                                try {
                                    
                                    $this->_session->open($this->_maxurl . self::FLT_URL . $_ftl_id);
                                    
                                    // : Wait for fleet name to be present on page within specified element
                                    $this->_tmp = $_fleet_name;
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("xpath", "//*[@id='toolbar']/div[contains(text(), '{$this->_tmp}')]");
                                    });
                                    // : End
                                    
                                    // : Wait for text of truck fleetnum to be present on page
                                    $this->_tmp = $_truck_fleetnum;
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("xpath", "//*/td[contains(text(), '{$this->_tmp}')]");
                                    });
                                    // : End
                                    
                                    $this->assertElementPresent("css selector", "div.toolbar-cell-update");
                                    $this->_session->element("css selector", "div.toolbar-cell-update")->click();
                                    
                                    // : Wait for Update Fleet Truck Link heading to be present in a table cell
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("xpath", "//*/td[contains(text(),'Update Fleet Truck Link')]");
                                    });
                                    
                                    $this->assertElementPresent("xpath", "//*/td[contains(text(), '$_truck_fleetnum')]");
                                    $this->assertElementPresent("xpath", "//*/td[contains(text(), '$_fleet_name')]");
                                    $this->assertElementPresent("xpath", "//*[@id='udo_FleetTruckLink-18_0_0_fleetTruckLinkBeginDate-18']");
                                    $this->assertElementPresent("xpath", "//*[@id='udo_FleetTruckLink-19_0_0_fleetTruckLinkEndDate-19']");
                                    $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                    
                                    // : Complete start and end dates according to the operation set
                                    switch ($_operation) {
                                        case 'update':
                                            {
                                                $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-18_0_0_fleetTruckLinkBeginDate-18']")->clear();
                                                $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-18_0_0_fleetTruckLinkBeginDate-18']")->sendKeys($_start_date);
                                                $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-19_0_0_fleetTruckLinkEndDate-19']")->clear();
                                                $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-19_0_0_fleetTruckLinkEndDate-19']")->sendKeys($_end_date);
                                                break;
                                            }
                                        case 'remove':
                                            {
                                                $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-19_0_0_fleetTruckLinkEndDate-19']")->clear();
                                                $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-19_0_0_fleetTruckLinkEndDate-19']")->sendKeys($_end_date);
                                                break;
                                            }
                                        case 'create':
                                        default:
                                            {
                                                $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-19_0_0_fleetTruckLinkEndDate-19']")->clear();
                                                $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-19_0_0_fleetTruckLinkEndDate-19']")->sendKeys($_start_date);
                                                break;
                                            }
                                    }
                                    // : End
                                    
                                    $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                                    // : End
                                    
                                    // : Wait for fleet name to be present on page within specified element
                                    $this->_tmp = $_fleet_name;
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("xpath", "//*[@id='toolbar']/div[contains(text(), '{$this->_tmp}')]");
                                    });
                                    // : End
                                    
                                    // : Wait for text of truck fleetnum to be present on page
                                    $this->_tmp = $_truck_fleetnum;
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("xpath", "//*/td[contains(text(), '{$this->_tmp}')]");
                                    });
                                    // : End
                                } catch (Exception $e) {
                                    $this->reportNewError($e->getMessage(), $value);
                                }
                            }
                            
                            // : Create new link for create operation only
                            if ($_operation == 'create') {
                                try {
                                    // : Load Fleet DataBrowser page
                                    $this->_session->open($this->_maxurl . self::FLEET_URL . $_fleet);
                                    
                                    $this->_tmp = $_fleet_name;
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("xpath", "//*[@id='toolbar']/div[contains(text(), '{$this->_tmp}')]");
                                    });
                                    
                                    // : Check for select box element and option Fleet Truck Link and click it
                                    $this->assertElementPresent("xpath", "//*[@id='subtabselector']/select/option[contains(text(),'Fleet Truck Link')]");
                                    $this->_session->element("xpath", "//*[@id='subtabselector']/select/option[contains(text(),'Fleet Truck Link')]")->click();
                                    // : End
                                    
                                    // : Wait for table and column header of table to read Truck
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("xpath", "//*[@id='OrderBy29911']/table/tbody/tr/td[1]/nobr[contains(text(),'Truck')]");
                                    });
                                    // : End
                                    
                                    // : Check for Create button and click it
                                    $this->assertElementPresent("css selector", "div#button-create");
                                    $this->_session->element("css selector", "div#button-create")->click();
                                    // : End
                                    
                                    // : Wait for text Create Fleet Truck Link
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("xpath", "//*/td[contains(text(),'Create Fleet Truck Link')]");
                                    });
                                    // : End
                                    
                                    $this->assertElementPresent("xpath", "//*/td[contains(text(),'$_fleet_name')]");
                                    $this->assertElementPresent("xpath", "//*[@id='udo_FleetTruckLink-9__0_truck_id-9']");
                                    $this->assertElementPresent("xpath", "//*[@id='udo_FleetTruckLink-18_0_0_fleetTruckLinkBeginDate-18']");
                                    $this->assertElementPresent("xpath", "//*[@id='udo_FleetTruckLink-19_0_0_fleetTruckLinkEndDate-19']");
                                    $this->assertElementPresent("css selector", "input[type=submit][name=save]");

                                    $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-9__0_truck_id-9']/option[text()='{$_truck_fleetnum}']")->click();
                                    
                                    $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-18_0_0_fleetTruckLinkBeginDate-18']")->clear();
                                    $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-18_0_0_fleetTruckLinkBeginDate-18']")->sendKeys($_start_date);
                                    
                                    $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-19_0_0_fleetTruckLinkEndDate-19']")->clear();
                                    
                                    if ($_end_date !== FALSE) {
                                        $this->_session->element("xpath", "//*[@id='udo_FleetTruckLink-19_0_0_fleetTruckLinkEndDate-19']")->sendKeys($_end_date);
                                    }
                                    
                                    $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                                    
                                    // : Wait for Fleet Truck Link page to reload and check for truck link
                                    $this->_tmp = $_truck_fleetnum;
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("xpath", "//*/a/nobr[contains(text(),'{$this->_tmp}')]");
                                    });
                                    // : End
                                    
                                } catch (Exception $e) {
                                    $this->reportNewError($e->getMessage(), $value);
                                }
                            }
                            // : End
                            
                            // : End
                        } catch (Exception $e) {
                            $this->reportNewError($e->getMessage(), $value);
                        }
                    }
                }
                // : End
            } catch (Exception $e) {
                $this->reportNewError($e->getMessage(), $value);
            }
        }
        
        // : Report errors if any occured
        if ($this->_errors) {
            $_errfile = dirname(__FILE__) . self::DS . $this->_errdir . self::DS . "error_report_" . date("Y-m-d_His") . ".csv";
            $this->ExportToCSV($_errfile, $this->_errors);
            echo "Exported error report to the following path and file: " . $_errfile;
        }
        // : End
        
        // : Tear Down
        // Click the logout link
        $this->_session->element('xpath', "//*[contains(@href,'/logout')]")->click();
        // Wait for page to load and for elements to be present on page
        $e = $w->until(function ($session)
        {
            return $session->element('css selector', 'input[id=identification]');
        });
        $this->assertElementPresent('css selector', 'input[id=identification]');
        // Terminate session
        $this->_session->close();
        // : End
    }
    
    // : Private Functions
    
    /**
     * MAXLive_FleetTruckLinkCommander::takeScreenshot($_session)
     * This is a function description for a selenium test function
     *
     * @param object: $_session            
     */
    private function takeScreenshot($_session, $_filename)
    {
        try {
            $_img = $_session->screenshot();
            $_data = base64_decode($_img);
            $_file = dirname(__FILE__) . self::DS . $this->_scrdir . self::DS . date("Y-m-d_His") . $_filename;
            $_success = file_put_contents($_file, $_data);
            if ($_success) {
                return $_file;
            } else {
                return FALSE;
            }
        } catch (Exception $e) {
            $this->reportNewError($e->getMessage());
            return FALSE;
        }
    }

    /**
     * MAXLive_FleetTruckLinkCommander::assertElementPresent($_using, $_value)
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
     * MAXLive_FleetTruckLinkCommander::getSelectedOptionValue($_using, $_value)
     * This is a function description for a selenium test function
     *
     * @param string: $_using            
     * @param string: $_value            
     */
    private function getSelectedOptionValue($_using, $_value)
    {
        try {
            $_result = FALSE;
            $_cnt = count($this->_session->elements($_using, $_value));
            for ($x = 1; $x <= $_cnt; $x ++) {
                $_selected = $this->_session->element($_using, $_value . "[$x]")->attribute("selected");
                if ($_selected) {
                    $_result = $this->_session->element($_using, $_value . "[$x]")->attribute("value");
                    break;
                }
            }
        } catch (Exception $e) {
            $_result = FALSE;
        }
        return ($_result);
    }

    /**
     * MAXLive_FleetTruckLinkCommander::ExportToCSV($csvFile, $arr)
     * From supplied csv file save data into multidimensional array
     *
     * @param string: $csvFile            
     * @param array: $_arr            
     */
    private function ExportToCSV($csvFile, $_arr)
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

    /**
     * MAXLive_FleetTruckLinkCommander::convertUnixToFormattedTime($_unixTime)
     * Convert unix time to formated datetime string Y-m-d H:i:s
     *
     * @param string: $_unixTime            
     */
    private function convertUnixToFormattedTime($_unixTime)
    {
        try {
            if ($_unixTime) {
                $_result = date("Y-m-d H:i:s", $_unixTime);
                if ($_result) {
                    return $_result;
                } else {
                    $this->_errors[] = "A call to function " . __FUNCTION__ . " failed because time convert failed.";
                    return FALSE;
                }
            } else {
                $this->_errors[] = "A call to function " . __FUNCTION__ . " failed because no time was given.";
                return FALSE;
            }
        } catch (Exception $e) {
            $this->_errors[] = "A call to function " . __FUNCTION__ . " failed because: " . $e->getMessage();
            return FALSE;
        }
    }

    /**
     * MAXLive_FleetTruckLinkCommander::reportNewError($_error, $_dataarr = null)
     * Report new error and add current record data been processed to error message
     *
     * @param string: $_error            
     * @param array: $_dataarr            
     */
    private function reportNewError($_error, $_dataarr = null)
    {
        try {
            $_num = count($this->_errors);
            $this->_errors[$_num]["errmsg"] = "Failed to update existing truck link with error message: " . $_error;
            if ($_dataarr) {
                foreach ($_dataarr as $recKey => $recVal) {
                    $this->_errors[$_num][$recKey] = $recVal;
                }
            }
            $this->takeScreenshot($this->_session, "updateTruckPrimaryFleet");
        } catch (Exception $e) {
            return FALSE;
        }
        return TRUE;
    }
    // : End
}
