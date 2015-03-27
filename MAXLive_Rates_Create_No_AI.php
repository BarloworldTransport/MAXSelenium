<?php
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
include_once dirname(__FILE__) . '/ReadExcelFile.php';
include_once 'PHPUnit/Extensions/PHPExcel/Classes/PHPExcel.php';
include_once dirname(__FILE__) . '/automationLibrary.php';

/**
 * MAXLive_Rates_Create.php
 *
 * @package MAXLive_Rates_Create
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
class MAXLive_Rates_Create extends PHPUnit_Framework_TestCase
{
    // : Constants
    const COULD_NOT_CONNECT_MYSQL = "Failed to connect to MySQL database";

    const MAX_NOT_RESPONDING = "MAX does not seem to be responding";

    const RATE_MISSING_DATA = "Route and rate entry does not have all its data";

    const FILE_NOT_FOUND = "The following path and filename could not be found: %s";

    const COULD_NOT_OPEN_FILE = "Could not open the specfied file %s";

    const FILE_EMPTY = "The following file is empty: %s";

    const COLUMN_VALIDATION_FAIL = "Not all columns are present in the following file %s";

    const PROCESS_FAIL = "An error occured while %s the %o %r";

    const DS = DIRECTORY_SEPARATOR;

    const BF = "0.00";

    const COUNTRY = "South Africa";

    const INI_FILE = "rates_data.ini";

    const INI_DIR = "ini";

    const XLS_CREATOR = "MAXLive_Rates_Create.php";

    const XLS_TITLE = "Error Report";

    const XLS_SUBJECT = "Errors caught while creating rates for subcontracts";

    const DELIMITER = ',';

    const ENCLOSURE = '"';
    
    // : Variables
    protected static $driver;

    protected $_dummy;

    protected $_session;

    protected $lastRecord;

    protected $to = 'cwright@bwtsgroup.com';

    protected $subject = 'MAX Selenium script report';

    protected $message;

    protected $_username;

    protected $_password;

    protected $_welcome;

    protected $_mode;

    protected $_dataDir;

    protected $_errDir;

    protected $_scrDir;

    protected $_wdport;

    protected $_logDir;

    protected $_browser;

    protected $_ip;

    protected $_proxyip;

    protected $_csv;

    protected $_maxurl;

    protected $_error = array();

    protected $_functionError = "";

    protected $_db;

    protected $_dbdsn = "mysql:host=%s;dbname=max2;charset=utf8;";

    protected $_dbuser = "root";

    protected $_dbpwd = "kaluma";

    protected $_dboptions = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => true
    );

    protected $_totals = array(
        "locations" => 0,
        "offloading" => 0,
        "rates" => 0
    );
    
    // : Public functions
    // : Getters
    
    /**
     * MAXLive_Rates_Create_No_AI::getFunctionErrorMsg()
     * Access protected variable used to store global error message
     *
     * @param string: $this->_functionError            
     */
    public function getFunctionErrorMsg()
    {
        return $this->_functionError;
    }
    
    // : End
    
    // : Setters
    /**
     * MAXLive_Rates_Create_No_AI::addErrorRecord($_errmsg, $_record, $_process)
     * Add error record to error array
     *
     * @param string: $_errmsg            
     * @param string: $_record            
     * @param string: $_process            
     */
    public function addErrorRecord($_errmsg, $_record, $_process)
    {
        $_erCount = count($this->_error);
        $this->_error[$_erCount + 1]["error"] = $_errmsg;
        $this->_error[$_erCount + 1]["record"] = $_record;
        $this->_error[$_erCount + 1]["type"] = $_process;
        $this->takeScreenshot();
    }
    // : End
    
    /**
     * MAXLive_Rates_Create_No_AI::stringHypenFix($_value)
     * Replace long hyphens in string to short hyphens as part of a problem
     * created when importing data from spreadsheets
     *
     * @param string: $_value            
     * @param string: $_result            
     */
    public function stringHypenFix($_value)
    {
        $_result = preg_replace("/–/", "-", $_value);
        return $_result;
    }
    
    // : Magic
    /**
     * MAXLive_Rates_Create_No_AI::__construct()
     * Class constructor
     */
    public function __construct()
    {
        $ini = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . self::INI_DIR . DIRECTORY_SEPARATOR . self::INI_FILE;
        if (is_file($ini) === FALSE) {
            echo "No " . self::INI_FILE . " file found. Please refer to documentation for script to determine which fields are required and their corresponding values." . PHP_EOL;
            return FALSE;
        }
        $data = parse_ini_file($ini);
        if ((array_key_exists("logdir", $data) && $data["logdir"]) && (array_key_exists("browser", $data) && $data["browser"]) && (array_key_exists("wdport", $data) && $data["wdport"]) && (array_key_exists("xls", $data) && $data["xls"]) && (array_key_exists("errordir", $data) && $data["errordir"]) && (array_key_exists("screenshotdir", $data) && $data["screenshotdir"]) && (array_key_exists("datadir", $data) && $data["datadir"]) && (array_key_exists("ip", $data) && $data["ip"]) && (array_key_exists("username", $data) && $data["username"]) && (array_key_exists("password", $data) && $data["password"]) && (array_key_exists("welcome", $data) && $data["welcome"]) && (array_key_exists("mode", $data) && $data["mode"])) {
            $this->_username = $data["username"];
            $this->_password = $data["password"];
            $this->_welcome = $data["welcome"];
            $this->_dataDir = $data["datadir"];
            $this->_errDir = $data["errordir"];
            $this->_scrDir = $data["screenshotdir"];
            $this->_logDir = $data["logdir"];
            $this->_mode = $data["mode"];
            $this->_ip = $data["ip"];
            $this->_wdport = $data["wdport"];
            $this->_proxyip = $data["proxy"];
            $this->_browser = $data["browser"];
            $this->_xls = $data["xls"];
            switch ($this->_mode) {
                case "live":
                    $this->_maxurl = automationLibrary::URL_LIVE;
                    break;
                default:
                    $this->_maxurl = automationLibrary::URL_TEST;
            }
        } else {
            echo "The correct data is not present in " . self::INI_FILE . ". Please confirm. Fields are username, password, welcome and mode" . PHP_EOL;
            return FALSE;
        }
    }

    /**
     * MAXLive_Rates_Create_No_AI::__destruct()
     * Class destructor
     * Allow for garbage collection
     */
    public function __destruct()
    {
        unset($this);
    }
    // : End
    public function progressLogFile($_file, $_category, $_currentRecord, $_passed, $_failed, $_process, $_progress)
    {
        $_logArray = (array) array();
        $_logArray[] = "Script Name: " . basename(__FILE__);
        $_logArray[] = "WebDriver Port: " . $this->_wdport;
        $_logArray[] = "Browser Used: " . $this->_browser;
        $_logArray[] = "Data File: " . $this->_xls;
        $_logArray[] = "Record Totals:";
        $_logArray[] = "Location Record: " . $this->_totals["locations"];
        $_logArray[] = "Offloading Customer: " . $this->_totals["offloading"];
        $_logArray[] = "Rates: " . $this->_totals["rates"];
        $_logArray[] = "Current Category of Data been processed: " . $_category;
        $_logArray[] = "Current Process: " . $_process;
        $_logArray[] = "Current Record: " . $_currentRecord;
	$_logArray[] = "Records Passed: " . $_passed;
	$_logArray[] = "Records Failed: " . $_failed;
        $_logArray[] = "Progress: " . $_progress;
        
        $_logstr = (string) "## BWT Automation Progress Log File" . PHP_EOL;
        
        foreach ($_logArray as $value) {
            $_logstr .= $value . PHP_EOL;
        }
        
        if ($_logstr) {
            $_fh = fopen($_file, 'w+');
            fwrite($_fh, $_logstr);
            fclose($_fh);
        }
    }

    /**
     * MAXLive_Rates_Create_No_AI::setUp()
     * Create new class object and initialize session for webdriver
     */
    public function setUp()
    {
        $wd_host = "http://localhost:$this->_wdport/wd/hub";
        self::$driver = new PHPWebDriver_WebDriver($wd_host);
        $desired_capabilities = array();
        $proxy = new PHPWebDriver_WebDriverProxy();
        $proxy->httpProxy = $this->_proxyip;
        $proxy->add_to_capabilities($desired_capabilities);
        $this->_session = self::$driver->session($this->_browser, $desired_capabilities);
    }

    /**
     * MAXLive_Rates_Create_No_AI::testCreateContracts()
     * Pull F and V Contract data and automate creation of F and V Contracts
     */
    public function testCreateRates()
    {
        
        // : Define local variables
        $_dateTimeStarted = date("Y-m-d H:i:s");
        $_dbStatus = FALSE;
        $_match = 0;
        $_process = (string) "";
        $_objectregistry_id = (int) 0;
        
        // Construct full path and filename for csv file using script home dir and data dir path to file
        $_file = dirname(__FILE__) . $this->_dataDir . DIRECTORY_SEPARATOR . $this->_xls;
        echo $_file . PHP_EOL;
        
        $_progressLogFile = $this->_logDir . DIRECTORY_SEPARATOR . "progressLogFile_" . basename(__FILE__, ".php") . "_{$this->_wdport}";
        echo $_progressLogFile . PHP_EOL;
        
        // : Error report columns for the spreadsheet data
        $_xlsColumns = array(
            "Error_Msg",
            "Record Detail",
            "Type"
        );
        
        // : Pull spreadsheet data and store into multi dimensional array
        
        // : Import Rates from spreadsheet
        $_xls1 = new ReadExcelFile($_file, "rates", "locations", "offloading", "bu", "customer");
        $_temp = $_xls1->getData();
        unset($_xls1);
        // : End
        
        // : Rearrange data in array into more orderly layout
        foreach ($_temp as $key => $value) {
            if ($value) {
                foreach ($value as $key2 => $value2) {
                    foreach ($value2 as $key3 => $value3) {
                        // stringHyphenFix function used to convert any long hyphens to short hyphens (xls import issue)
                        $_data[$key][$key3][$key2] = $this->stringHypenFix($value3);
                    }
                }
            } else {
                $_data[$key] = NULL;
            }
        }
        // : End
        // : Store total of records for each section of data
        
        if (array_key_exists('locations', $_data)) {
            $this->_totals['locations'] = count($_data['locations']);
        }
        if (array_key_exists('offloading', $_data)) {
            $this->_totals['offloading'] = count($_data['offloading']);
        }
        if (array_key_exists('rates', $_data)) {
            $this->_totals['rates'] = count($_data['rates']);
        }
        // : End
        
        if ((isset($_data)) && (array_key_exists('bu', $_data) && array_key_exists('customer', $_data)) && (array_key_exists('rates', $_data) || array_key_exists('locations', $_data) || array_key_exists('offloading', $_data))) {
            
            // : Create a persistant connection to the database
            $_mysqlDsn = preg_replace("/%s/", $this->_ip, $this->_dbdsn);
            if ($this->openDB($_mysqlDsn, $this->_dbuser, $this->_dbpwd, $this->_dboptions) !== FALSE) {
                $_dbStatus = TRUE;
            } else {
                throw new Exception(self::COULD_NOT_CONNECT_MYSQL);
            }
            // : End
            
            // : Initiate Session
            $session = $this->_session;
            $this->_session->setPageLoadTimeout(90);
            // Create a reference to the session object for use with waiting for elements to be present
            $w = new PHPWebDriver_WebDriverWait($this->_session, 120);
            // : End
            
            // : Login
            try {
                // Load MAX home page
                $this->_session->open($this->_maxurl);
                // : Wait for page to load and for elements to be present on page
                if ($this->_mode == "live" || $this->_mode == "test") {
                    $e = $w->until(function ($session)
                    {
                        return $session->element('css selector', "#contentFrame");
                    });
                    $iframe = $this->_session->element('css selector', '#contentFrame');
                    $this->_session->switch_to_frame($iframe);
                }
                $e = $w->until(function ($session)
                {
                    return $session->element('css selector', 'input[id=identification]');
                });
                // : End
                $this->assertElementPresent('css selector', 'input[id=identification]');
                $this->assertElementPresent('css selector', 'input[id=password]');
                $this->assertElementPresent('css selector', 'input[name=submit][type=submit]');
                $e->sendKeys($this->_username);
                $e = $this->_session->element('css selector', 'input[id=password]');
                $e->sendKeys($this->_password);
                $e = $this->_session->element('css selector', 'input[name=submit][type=submit]');
                $e->click();
                // Switch out of frame
                if ($this->_mode == "live" || $this->_mode == "test") {
                    $this->_session->switch_to_frame();
                }
                
                // : Wait for page to load and for elements to be present on page
                if ($this->_mode == "live" || $this->_mode == "test") {
                    $e = $w->until(function ($session)
                    {
                        return $session->element('css selector', "#contentFrame");
                    });
                    $iframe = $this->_session->element('css selector', '#contentFrame');
                    $this->_session->switch_to_frame($iframe);
                }
                $e = $w->until(function ($session)
                {
                    return $session->element("xpath", "//*[text()='" . $this->_welcome . "']");
                });
                $this->assertElementPresent("xpath", "//*[text()='" . $this->_welcome . "']");
                // Switch out of frame
                if ($this->_mode == "live" || $this->_mode == "test") {
                    $this->_session->switch_to_frame();
                }
            } catch (Exception $e) {
                throw new Exception("Error: Failed to log into MAX." . PHP_EOL . $e->getMessage());
                $this->takeScreenshot();
            }
            // : End
            
            // : Get objectregistry_id for udo_Customer
            $_sqlquery = preg_replace("/%s/", automationLibrary::OBJREG_CUSTOMER, automationLibrary::SQL_QUERY_OBJREG);
            $result = $this->queryDB($_sqlquery);
            if (count($result) != 0) {
                $objectregistry_id = intval($result[0]["ID"]);
            } else {
                throw new Exception("Error: Object registry record for udo_customer not found.");
            }
            // : End
            
            // : Load Planningboard to rid of iframe loading on every page from here on
            $this->_session->open($this->_maxurl . automationLibrary::URL_PB);
            $e = $w->until(function ($session)
            {
                return $session->element("xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]");
            });
            // : End
            
            /**
             * VARIABLE AND DATA PREP
             */
            
            if ($_data['customer']) {
                echo $_data['customer'][1]['customerName'] . PHP_EOL;
                $_sqlquery = preg_replace("/%t/", $_data['customer'][1]['customerName'], automationLibrary::SQL_QUERY_CUSTOMER);
                $result = $this->queryDB($_sqlquery);
                echo 'INFO: SQL Query - Get customer ID: ' . $_sqlquery . PHP_EOL;
                if ($result) {
                    $_customerID = intval($result[0]["ID"]);
                } else {
                    $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                    throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                }
            } else {
                throw new Exception(automationLibrary::ERR_NO_CUSTOMER_DATA);
            }
            
            /**
             * END
             */
            
            /**
             * LOCATION SECTION
             */
            
            // Define full path and file name for log file
            $_progressLogFile = $this->_logDir . DIRECTORY_SEPARATOR . basename(__FILE__, "php") . "_{$this->_wdport}_";
            echo $_progressLogFile . PHP_EOL;
            
            // If location data exists then continue
            if (array_key_exists('locations', $_data)) {
                
                $_process = "linkCustomerLocations";
                $_complete = 0;
                $_currentRecordNum = 0;
                $_totalRecords = count($_data['locations']);
                $_recordsProcessed = 0;
                $_recordsFailed = 0;
                
                foreach ($_data['locations'] as $_locKey => $_locValue) {
                    
                    try {
                        
                        // : Set variables
                        $_locationID = 0;
                        $_customerLocationLinkID = 0;
                        $_bunitID = 0;
                        $_currentRecord = "";
                        $_recordsAll = $_recordsFailed + $_recordsProcessed;
                        $_complete = (($_recordsAll / $this->_totals['locations']) * 100);
                        $_progressStr = "Locations Progress: " . strval($_recordsAll) . "/" . strval($this->_totals['locations']) . ", Section Progress: {$_complete}%";
                        
                        foreach ($_locValue as $_key1 => $_value1) {
                            $_currentRecord .= "[$_key1]=>$_value1;";
                        }
                        $this->progressLogFile($_progressLogFile, "locations", $_currentRecord, $_recordsFailed, $_recordsProcessed, $_process, $_progressStr);
                        
                        // Get IDs for point and customer link location (if link exists)
                        $_sqlquery = preg_replace("/%n/", $_locValue['pointName'], automationLibrary::SQL_QUERY_LOCATION);
                        $_sqlquery = preg_replace("/%t/", automationLibrary::_TYPE_POINT, $_sqlquery);
                        $result = $this->queryDB($_sqlquery);
                        if (count($result) != 0) {
                            $_locationID = intval($result[0]["ID"]);
                        } else {
                            $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                            throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                        }
                        
                        $_sqlquery = preg_replace("/%l/", $_locationID, automationLibrary::SQL_QUERY_CUSTOMER_LOCATION_LINK);
                        $_sqlquery = preg_replace("/%c/", $_customerID, $_sqlquery);
                        $result = $this->queryDB($_sqlquery);
                        
                        automationLibrary::CONSOLE_OUTPUT("Customer location link query to get ID", "Result of query run: ", "sql", $_sqlquery, $result);
                        
                        if (count($result) != 0) {
                            $_customerLocationLinkID = intval($result[0]["ID"]);
                        }
                        
                        // If customer location link does not exist then create it
                        if (! $_customerLocationLinkID) {
                            // Load URL for MAX customers page
                            $this->_session->open($this->_maxurl . automationLibrary::URL_CUSTOMER . $_customerID);
                            // Wait for element = #subtabselector
                            $e = $w->until(function ($session)
                            {
                                return $session->element("css selector", "#subtabselector");
                            });
                            // Select option from select box
                            $this->_session->element("xpath", "//*[@id='subtabselector']/select/option[text()='Locations']")->click();
                            // Wait for element = #button-create
                            $e = $w->until(function ($session)
                            {
                                return $session->element("css selector", "#button-create");
                            });
                            // Click element - button
                            $this->_session->element("css selector", "#button-create")->click();
                            // Wait for element
                            $e = $w->until(function ($session)
                            {
                                return $session->element("xpath", "//*[@id='udo_CustomerLocations-5__0_location_id-5']");
                            });
                            $this->assertElementPresent("link text", "Create Location");
                            $this->assertElementPresent("xpath", "//*[@id='udo_CustomerLocations-5__0_location_id-5']");
                            $this->assertElementPresent("xpath", "//*[@id='udo_CustomerLocations-8__0_type-8']");
                            $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                            
                            // Select new location from select box
                            $this->_session->element("xpath", "//*[@id='udo_CustomerLocations-5__0_location_id-5']/option[text()='" . $_locValue['pointName'] . "']")->click();
                            // Select type for new location from select box
                            $this->_session->element("xpath", "//*[@id='udo_CustomerLocations-8__0_type-8']/option[text()='" . $_locValue['type'] . "']")->click();
                            // Click the submit button
                            $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                            $this->takeScreenshot("createCustomerLocation");
                        }
                        
                        // : Create Business Unit Link for Point Link
                        $_sqlquery = preg_replace("/%l/", $_locationID, automationLibrary::SQL_QUERY_CUSTOMER_LOCATION_LINK);
                        $_sqlquery = preg_replace("/%c/", $_customerID, $_sqlquery);
                        $_result = $this->queryDB($_sqlquery);
                        
                        // If BU Link does not exist for customer location then create it else dont
                        if ($_result) {
                            
                            $_process = "createCustomerLocationLinkBU";
                            $_customerLocationLinkID = $_result[0]["ID"];
                            
                            if ($_customerLocationLinkID) {
                                
                                foreach ($_data['bu'] as $_buKey => $_buValue) {
                                    try {
                                        
                                        // Get business unit ID
                                        $_sqlquery = preg_replace("/%s/", $_buValue['bunit'], automationLibrary::SQL_QUERY_BUNIT);
                                        $result = $this->queryDB($_sqlquery);
                                        if (count($result) != 0) {
                                            $_bunitID = intval($result[0]["ID"]);
                                        }
                                        
                                        $_sqlquery = preg_replace("/%l/", $_customerLocationLinkID, automationLibrary::SQL_QUERY_CUSTOMER_LOCATION_BU_LINK);
                                        $_sqlquery = preg_replace("/%b/", $_bunitID, $_sqlquery);
                                        $_result_bu = $this->queryDB($_sqlquery);
                                        
                                        if (! $_result_bu) {
                                            
                                            // Load URL for the customer location business unit databrowser page
                                            $_url = $this->_maxurl . automationLibrary::URL_CUST_LOCATION_BU . $_customerLocationLinkID;
                                            $this->_session->open($_url);
                                            // Wait for element
                                            $e = $w->until(function ($session)
                                            {
                                                return $session->element("css selector", "#button-create");
                                            });
                                            // Click element = button-create
                                            $this->_session->element("css selector", "#button-create")->click();
                                            
                                            // Wait for element = Page heading
                                            $e = $w->until(function ($session)
                                            {
                                                return $session->element("xpath", "//*[contains(text(),'Create Customer Locations - Business Unit')]");
                                            });
                                            
                                            $this->assertElementPresent("xpath", "//*[@id='udo_CustomerLocationsBusinessUnit_link-2__0_businessUnit_id-2']");
                                            $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                            
                                            $this->_session->element("xpath", "//*[@id='udo_CustomerLocationsBusinessUnit_link-2__0_businessUnit_id-2']/option[text()='" . $_buValue['bunit'] . "']")->click();
                                            // Click the submit button
                                            $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                                            // Wait for element
                                            $this->takeScreenshot("createCustomerLocationBULink");
                                            $e = $w->until(function ($session)
                                            {
                                                return $session->element("css selector", "#button-create");
                                            });
                                        }
                                    } catch (Exception $e) {
                                        $_errmsg = preg_replace("/%s/", $_process, automationLibrary::ERR_PROCESS_FAILED_UNEXPECTEDLY);
                                        $_errmsg = preg_replace("/%e/", $e->getMessage(), $_errmsg);
                                        $this->addErrorRecord($_errmsg, $_currentRecord, $_process);
                                    }
                                }
                            }
                        } else {
                            $_errmsg = preg_replace("/%d/", $_process, automationLibrary::ERR_COULD_NOT_FIND_RECORD_USING_URL);
                            $_errmsg = preg_replace("/%u/", $_url, $_errmsg);
                            throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                        }
                        $_recordsProcessed ++;
                        // : End
                    } catch (Exception $e) {
                        $_recordsFailed ++;
                        $this->addErrorRecord($e->getMessage(), $_currentRecord, $_process);
                    }
                    $_currentRecordNum ++;
                }
            }
            /**
             * END
             */
            
            /**
             * OFFLOADING CUSTOMER SECTION
             */
            
            // If offloading customer data exists then continue
            if (array_key_exists("offloading", $_data)) {
               
                $_complete = 0;
                $_currentRecordNum = 0;
                $_recordsProcessed = 0;
                $_recordsFailed = 0;
 
                foreach ($_data['offloading'] as $_offloadKey => $_offloadValues) {
                    
		    try {

                    $_offloadingCustomerLinkID = 0;
                    $_bunitID = 0;
                    $_currentRecord = "";
                    $_recordsAll = $_recordsFailed + $_recordsProcessed;
                    $_complete = (($_recordsAll / $this->_totals['offloading']) * 100);
                    $_progressStr = "Offloading Customer Progress: " . strval($_recordsAll) . "/" . strval($this->_totals['offloading']) . ", Section Progress: {$_complete}%";
                        
                    $_currentRecord = "";
                    foreach ($_offloadValues as $_key1 => $_value1) {
                        $_currentRecord .= "[$_key1]=>$_value1;";
                    }

                    $this->progressLogFile($_progressLogFile, "offloading customers", $_currentRecord, $_recordsFailed, $_recordsProcessed, $_process, $_progressStr);
                    
                    // : If offloading customer does exist then check if offloading customer is linked to the customer and store the link ID
                    $_sqlquery = preg_replace("/%o/", $_offloadValues['tradingName'], automationLibrary::SQL_QUERY_OFFLOADING_CUSTOMER);
                    $_sqlquery = preg_replace("/%c/", $_customerID, $_sqlquery);
                    $_sqlquery1 = $this->queryDB($_sqlquery);
                    
		    if (count($_sqlquery1) != 0) {
                        $_offloadingCustomerLinkID = intval($_sqlquery1[0]["ID"]);
                    } else {
                        $_offloadingCustomerLinkID = 0;
                    }
                    // : End
                        
			// : Create and link Offloading Customer
                        if (! $_offloadingCustomerLinkID) {
                            
                            $_process = "createOffloadingCustomerCustomerLink";
                            // : Load customer data browser page for Customer
                            $_url = $this->_maxurl . automationLibrary::URL_CUSTOMER . $_customerID;
                            $this->_session->open($_url);
                            
                            // Wait for element = Page heading
                            $e = $w->until(function ($session)
                            {
                                return $session->element("css selector", "#subtabselector");
                            });
                            $this->_session->element("xpath", "//*[@id='subtabselector']/select/option[text()='Offloading Customers where Offloading Customer is " . $_data['customer'][1]['customerName'] . "']")->click();
                            
                            // Wait for element = Page heading
                            $e = $w->until(function ($session)
                            {
                                return $session->element("css selector", "#button-create");
                            });
                            $this->_session->element("css selector", "#button-create")->click();
                            
                            // Wait for element = Page heading
                            $e = $w->until(function ($session)
                            {
                                return $session->element("xpath", "//*[contains(text(),'Capture the details of Offloading Customers')]");
                            });
                            
                            $_process = "createOffloadingCustomerCustomerLink";
                            
                            $this->assertElementPresent("xpath", "//*[@id='udo_OffloadingCustomers-3__0_customer_id-3']");
                            $this->assertElementPresent("xpath", "//*[@id='udo_OffloadingCustomers-6__0_offloadingCustomer_id-6']");
                            $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                            
                            $this->_session->element("xpath", "//*[@id='udo_OffloadingCustomers-3__0_customer_id-3']/option[text()='" . $_data['customer'][1]['customerName'] . "']")->click();
                            $this->_session->element("xpath", "//*[@id='udo_OffloadingCustomers-6__0_offloadingCustomer_id-6']/option[text()='" . $_offloadValues['tradingName']  . "']")->click();
                            $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                            
                            // : Wait for Create Button to present on page before continuining
                            $e = $w->until(function ($session)
                            {
                                return $session->element("css selector", "#button-create");
                            });
                            // : End
			}
                            
                            // : Create Business Unit Link for Offloading Customer Link
                            
                            $myQuery = preg_replace("/%o/", $_offloadValues["tradingName"], automationLibrary::SQL_QUERY_OFFLOADING_CUSTOMER);
                            $myQuery = preg_replace("/%t/", $_customerID, $myQuery);
                            $sqlResult = $this->queryDB($myQuery);

			    if (count($sqlResult) != 0) {
        	                $_offloadingCustomerLinkID = intval($sqlResult[0]["ID"]);
                	    } else {
	                        $_offloadingCustomerLinkID = 0;
	                    }

                            if ($_offloadingCustomerLinkID) {
				foreach ($_data['bu'] as $buKey => $buValue) {
				
                            	$myQuery = preg_replace("/%s/", $_buValue['bunit'], automationLibrary::SQL_QUERY_BUNIT);
        	                $sqlResultBU = $this->queryDB($myQuery);
				if ($sqlResultBU) {
					$_bID = $sqlResultBU[0]['ID'];

                            	$myQuery = preg_replace("/%o/", $_offloadingCustomerLinkID, automationLibrary::SQL_QUERY_OFFLOAD_BU_LINK);
	                        $myQuery = preg_replace("/%b/", $_bID, $myQuery);
        	                $sqlResult = $this->queryDB($myQuery);

				// Check if offloadingcustomer_bulink exists. If it doesnt exist then create the link
				if (count($sqlResult) == 0) {

                                $_process = "create business unit link for offloading customer link";
                                $this->_session->open($this->_maxurl . automationLibrary::URL_OFFLOAD_CUST_BU . $_offloadingCustomerLinkID);
                                
                                // Wait for element = #subtabselector
                                $e = $w->until(function ($session)
                                {
                                    return $session->element("css selector", "#subtabselector");
                                });
                                $this->_session->element("xpath", "//*[@id='subtabselector']/select/option[text()='Offloading Customers - Business Unit']")->click();
                                
                                // Wait for element = #button-create
                                $e = $w->until(function ($session)
                                {
                                    return $session->element("xpath", "//div[@id='button-create']");
                                });
                                $this->_session->element("xpath", "//div[@id='button-create']")->click();
                                
                                // Wait for element = Page Heading
                                $e = $w->until(function ($session)
                                {
                                    return $session->element("xpath", "//*[contains(text(),'Create Offloading Customers - Business Unit')]");
                                });
                                
                                $this->assertElementPresent("xpath", "//*[@id='udo_OffloadingCustomersBusinessUnit_link-2__0_businessUnit_id-2']");
                                $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                
                                $this->_session->element("xpath", "//*[@id='udo_OffloadingCustomersBusinessUnit_link-2__0_businessUnit_id-2']/option[text()='" . $buValue["bunit"] . "']")->click();
                                $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                                
                                // Wait for element = #button-create
                                $e = $w->until(function ($session)
                                {
                                    return $session->element("xpath", "//div[@id='button-create']");
                                });
				}
				}
				}
                            } else {
                                throw new Exception("Could not find offloading customer record: " . $_offloadValues['tradingName']);
                            }
		    	$_recordsProcessed++; 	
                    } catch (Exception $e) {
			$_recordsFailed++;
                        $this->addErrorRecord($e->getMessage(), $_currentRecord, $_process);
                    }
		    $_currentRecordNum++;
                    // : End
                }
            }
            
            /**
             * END
             */
            
            /**
             * ROUTE AND RATE SECTION
             */
            
            // If rates data exists then continue
            if (array_key_exists("rates", $_data)) {
                
                // : Create Route
                
                $_complete = 0;
                $_currentRecordNum = 0;
                $_recordsProcessed = 0;
                $_recordsFailed = 0;
                
                foreach ($_data['rates'] as $_ratesKey => $_ratesValues) {
                    try {
                        // : Variable preparation
                        $_truckTypeID = 0;
                        $_rateTypeID = 0;
                        $_bunitID = 0;
                        $_locationFromID = 0;
                        $_locationToID = 0;
                        $_routeID = 0;
                        $_rateID = 0;
                        $_rateValueID = 0;
                        
                        $_recordsAll = $_recordsFailed + $_recordsProcessed;
                        $_complete = (($_recordsAll / $this->_totals['rates']) * 100);
                        $_progressStr = "Rates Progress: " . strval($_recordsAll) . "/" . strval($this->_totals['rates']) . ", Section Progress: {$_complete} %";
                        // : End
                        
                        $_currentRecord = "";
                        foreach ($_ratesValues as $_key1 => $_value1) {
                            $_currentRecord .= "[$_key1]=>$_value1;";
                        }
                        
                        $this->progressLogFile($_progressLogFile, "rates", $_currentRecord, $_recordsFailed, $_recordsProcessed, "create rate", $_progressStr);
                        
                        // Get truck description ID
                        $_sqlquery = preg_replace("/%d/", $_ratesValues['truckType'], automationLibrary::SQL_QUERY_TRUCK_TYPE);
                        $result = $this->queryDB($_sqlquery);
                        if (count($result) != 0) {
                            $_truckTypeID = intval($result[0]["ID"]);
                        } else {
                            $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                            throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                        }
                        
                        // Get rate type ID
                        $_sqlquery = preg_replace("/%s/", $_ratesValues['rateType'], automationLibrary::SQL_QUERY_RATE_TYPE);
                        $result = $this->queryDB($_sqlquery);
                        if (count($result) != 0) {
                            $_rateTypeID = intval($result[0]["ID"]);
                        } else {
                            $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                            throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                        }
                        
                        // Get business unit ID
                        $_sqlquery = preg_replace("/%s/", $_ratesValues['bunit'], automationLibrary::SQL_QUERY_BUNIT);
                        $result = $this->queryDB($_sqlquery);
                        if (count($result) != 0) {
                            $_bunitID = intval($result[0]["ID"]);
                        } else {
                            $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                            throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                        }
                        
                        // : Get IDs for cities
                        $_sqlquery = preg_replace("/%n/", $_ratesValues['locationFromTown'], automationLibrary::SQL_QUERY_LOCATION);
                        $_sqlquery = preg_replace("/%t/", automationLibrary::_TYPE_CITY, $_sqlquery);
                        $result = $this->queryDB($_sqlquery);
                        automationLibrary::CONSOLE_OUTPUT("Run query to city ID SQL query: ", "Query results: ", "sql", $_sqlquery, $result);
                        if (count($result) != 0) {
                            $_locationFromID = intval($result[0]["ID"]);
                        } else {
                            $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                            throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                        }
                        
                        $_sqlquery = preg_replace("/%n/", $_ratesValues['locationToTown'], automationLibrary::SQL_QUERY_LOCATION);
                        $_sqlquery = preg_replace("/%t/", automationLibrary::_TYPE_CITY, $_sqlquery);
                        $result = $this->queryDB($_sqlquery);
                        if (count($result) != 0) {
                            $_locationToID = intval($result[0]["ID"]);
                        } else {
                            $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                            throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                        }
                        // : End
                        
                        // : Check if route and rate exists
                        
                        // Check and store route ID if exists
                        if ($_locationFromID && $_locationToID) {
                            $_sqlquery = preg_replace("@%f@", $_locationFromID, automationLibrary::SQL_QUERY_ROUTE);
                            $_sqlquery = preg_replace("@%t@", $_locationToID, $_sqlquery);
                            $_result = $this->queryDB($_sqlquery);
                            automationLibrary::CONSOLE_OUTPUT("Run query to check for rate using SQL query: ", "Query results: ", "sql", $_sqlquery, $_result);
                            if (count($_result) != 0) {
                                $_routeID = $_result[0]["ID"];
                            } else {
                                $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                                throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                            }
                        }
                        
                        // : Check and store rate ID if exists
                        if ($_routeID && $_truckTypeID && $_rateTypeID && $_bunitID && $_ratesValues['contribModel']) {
                            // "select ID from udo_rates where route_id = %ro and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and model='%m' and businessUnit_id=%b and rateType_id=%ra;",
                            $_sqlquery = preg_replace("@%ro@", $_routeID, automationLibrary::SQL_QUERY_RATE);
                            $_sqlquery = preg_replace("@%r@", $_rateTypeID, $_sqlquery);
                            $_sqlquery = preg_replace("@%g@", $objectregistry_id, $_sqlquery);
                            $_sqlquery = preg_replace("@%c@", $_customerID, $_sqlquery);
                            $_sqlquery = preg_replace("@%d@", $_truckTypeID, $_sqlquery);
                            $_sqlquery = preg_replace("@%m@", $_ratesValues['contribModel'], $_sqlquery);
                            $_sqlquery = preg_replace("@%b@", $_bunitID, $_sqlquery);
                            $_result = $this->queryDB($_sqlquery);
                            automationLibrary::CONSOLE_OUTPUT("Run query to check for rate using SQL query: ", "Query results: ", "sql", $_sqlquery, $_result);
                            if (count($_result) != 0) {
                                $_rateID = $_result[0]["ID"];
                            }
                        }
                    } catch (Exception $e) {
                        $_errmsg = preg_replace("/%s/", $e->getMessage(), automationLibrary::ERR_PROCESS_FAILED_UNEXPECTEDLY);
                        $this->addErrorRecord($e->getMessage(), $_currentRecord, $_process);
                    }
                    // : End
                    
                    // : End
                    
                    // : Create Rate
                    // : End
                    
                    // : Check if route and rate exists for customer and create route and rate if they dont exist
                    try {
                        
                        if (! $_rateID) {
                            
                            // Concatenate string for route name
                            $_routeName = $_ratesValues['locationFromTown'] . " TO " . $_ratesValues['locationToTown'];
                            
                            $_process = "createRate";
                            
                            // Load the MAX customer page
                            $_url = $this->_maxurl . automationLibrary::URL_CUSTOMER . $_customerID;
                            $this->_session->open($_url);
                            
                            // Wait for element = #subtabselector
                            $e = $w->until(function ($session)
                            {
                                return $session->element("css selector", "#subtabselector");
                            });
                            // Select Rates from the select box
                            $this->_session->element("xpath", "//*[@id='subtabselector']/select/option[text()='Rates']")->click();
                            
                            // Wait for element = #button-create
                            $e = $w->until(function ($session)
                            {
                                return $session->element("css selector", "#button-create");
                            });
                            // Click element - #button-create
                            $this->_session->element("css selector", "#button-create")->click();
                            
                            // Wait for element Page Heading
                            $e = $w->until(function ($session)
                            {
                                return $session->element("xpath", "//*[@id='udo_Rates-31__0_route_id-31']");
                            });
                            
                            // : End
                            $_process = "createRate";
                            $this->assertElementPresent("xpath", "//*[@id='udo_Rates-30__0_rateType_id-30']");
                            $this->assertElementPresent("xpath", "//*[@id='udo_Rates-4__0_businessUnit_id-4']");
                            $this->assertElementPresent("xpath", "//*[@id='udo_Rates-36__0_truckDescription_id-36']");
                            $this->assertElementPresent("xpath", "//*[@id='udo_Rates-20__0_model-20']");
                            $this->assertElementPresent("xpath", "//*[@id='checkbox_udo_Rates-15_0_0_enabled-15']");
                            $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                            
                            $this->_session->element("xpath", "//*[@id='udo_Rates-31__0_route_id-31']/option[text()='" . $_routeName . "']")->click();
                            $this->_session->element("xpath", "//*[@id='udo_Rates-30__0_rateType_id-30']/option[text()='" . $_ratesValues['rateType'] . "']")->click();
                            $this->_session->element("xpath", "//*[@id='udo_Rates-4__0_businessUnit_id-4']/option[text()='" . $_data['bu'][1]['bunit'] . "']")->click();
                            $this->_session->element("xpath", "//*[@id='udo_Rates-36__0_truckDescription_id-36']/option[text()='" . $_ratesValues['truckType'] . "']")->click();
                            $this->_session->element("xpath", "//*[@id='udo_Rates-20__0_model-20']/option[text()='" . $_ratesValues['contribModel'] . "']")->click();
                            $this->_session->element("xpath", "//*[@id='checkbox_udo_Rates-15_0_0_enabled-15']")->click();
                            $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                            
                            $this->takeScreenshot("createRate");
                            
                            // Wait for element = #button-create
                            try {
                                $e = $w->until(function ($session)
                                {
                                    return $session->element("css selector", "#button-create");
                                });
                            } catch (PHPWebDriver_NoSuchElementWebDriverError $e) {
                                $_errmsg = preg_replace("/%s/", "#button-create", automationLibrary::ERR_COULD_NOT_FIND_ELEMENT);
                                $_errmsg = preg_replace("/%e/", $e->getMessage(), $_errmsg);
                                throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                            }
                        }
                        // : End
                        
                        // : Check and store rate ID if exists
                        if ($_routeID && $_truckTypeID && $_rateTypeID && $_bunitID && $_ratesValues['contribModel']) {
                            // "select ID from udo_rates where route_id = %ro and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and model='%m' and businessUnit_id=%b and rateType_id=%ra;",
                            $_sqlquery = preg_replace("@%ro@", $_routeID, automationLibrary::SQL_QUERY_RATE);
                            $_sqlquery = preg_replace("@%r@", $_rateTypeID, $_sqlquery);
                            $_sqlquery = preg_replace("@%g@", $objectregistry_id, $_sqlquery);
                            $_sqlquery = preg_replace("@%c@", $_customerID, $_sqlquery);
                            $_sqlquery = preg_replace("@%d@", $_truckTypeID, $_sqlquery);
                            $_sqlquery = preg_replace("@%m@", $_ratesValues['contribModel'], $_sqlquery);
                            $_sqlquery = preg_replace("@%b@", $_bunitID, $_sqlquery);
                            $_result = $this->queryDB($_sqlquery);
                            automationLibrary::CONSOLE_OUTPUT("Second run query to check for rate using SQL query: ", "Query results: ", "sql", $_sqlquery, $_result);
                            if (count($_result) != 0) {
                                $_rateID = $_result[0]["ID"];
                            } else {
                                $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                                throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                            }
                        }
                        // : End
                        
                        // : End
                        $_dateRangeValues = array();
                        $_dateRangeValues["Rate"] = $_ratesValues['rate'];
                        $_dateRangeValues["DaysPerMonth"] = $_ratesValues['daysPerMonth'];
                        $_dateRangeValues["DaysPerTrip"] = $_ratesValues['daysPerTrip'];
                        $_dateRangeValues["ExpectedDistance"] = $_ratesValues['expectedKms'];
                        $_dateRangeValues["ExpectedEmptyKms"] = $_ratesValues['expectedEmptyKms'];
                        $_dateRangeValues["FuelConsumptionForRoute"] = $_ratesValues['fuelConsumption'];
                        $_dateRangeValues["Fleet"] = $_ratesValues['fleetValue'];
                        
                        // : Create rate value for route and rate
                        foreach ($_dateRangeValues as $_drvKey => $_drvValue) {
                            $_process = "createDateRangeValuesForRate";
                            if ($_drvValue) {
                                try {
                                    // Prepare url string to load next
                                    $rateurl = preg_replace("/%s/", $_rateID, automationLibrary::URL_RATEVAL);
                                    // Load URL for route and rate databrowser page
                                    $this->_session->open($this->_maxurl . $rateurl);
                                    
                                    // Wait for element = #subtabselector
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("css selector", "#subtabselector");
                                    });
                                    // Click element - #subtabselector
                                    $this->_session->element("xpath", "//*[@id='subtabselector']/select/option[text()='" . $_drvKey . " Values" . "']")->click();
                                    
                                    // : Force wait
                                    sleep(1);
                                    
                                    // Wait for element = #button-create
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("css selector", "#button-create");
                                    });
                                    // Click element - #button-create
                                    $this->_session->element("css selector", "#button-create")->click();
                                    
                                    // Wait for element = #button-create
                                    $e = $w->until(function ($session)
                                    {
                                        return $session->element("xpath", "//*[contains(text(),'Create Date Range Values')]");
                                    });
                                    
                                    $this->assertElementPresent("xpath", "//*[@id='DateRangeValue-2_0_0_beginDate-2']");
                                    $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                    
                                    $this->_session->element("xpath", "//*[@id='DateRangeValue-2_0_0_beginDate-2']")->clear();
                                    $this->_session->element("xpath", "//*[@id='DateRangeValue-2_0_0_beginDate-2']")->sendKeys(date("Y-m-01 00:00:00"));
                                    
                                    if ($_drvKey != "Fleet") {
                                        $this->assertElementPresent("xpath", "//*[@id='DateRangeValue-20_0_0_value-20']");
                                        
                                        if ($_drvKey == "Rate") {
                                            // Remove any currency symbols from rate value
                                            $drv = preg_replace("@[Rr|\$]@", "", $_drvValue);
                                            
                                            // Convert supplied rate value number format to => 2 decimal spaces, point decimal seperator, and no thousands comma seperator
                                            $drv = strval((number_format(floatval($drv), 2, ".", "")));
                                        } else {
                                            // Convert supplied rate value number format to => 2 decimal spaces, point decimal seperator, and no thousands comma seperator
                                            $drv = strval((number_format(floatval($_drvValue), 2, ".", "")));
                                        }
                                        $this->_session->element("xpath", "//*[@id='DateRangeValue-20_0_0_value-20']")->clear();
                                        $this->_session->element("xpath", "//*[@id='DateRangeValue-20_0_0_value-20']")->sendKeys($drv);
                                    } else {
                                        $this->assertElementPresent("xpath", "//*[@id='DateRangeValue-20__0_value-20']");
                                        // If rate contribution value is fleet value then select the value from a select box
                                        $this->_session->element("xpath", "//*[@id='DateRangeValue-20__0_value-20']/option[text()='$_drvValue']")->click();
                                    }
                                    $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                                    sleep(1);
                                    $this->takeScreenshot("createDateRangeValue" . $_drvKey);
                                } catch (Exception $e) {
                                    $_errmsg = preg_replace("/%s/", $e->getMessage(), automationLibrary::ERR_PROCESS_FAILED_UNEXPECTEDLY);
                                    throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                                }
                            }
                        }
                        // : End
                        $_recordsProcessed ++;
                    } catch (Exception $e) {
                        $_recordsFailed ++;
                        $_errmsg = preg_replace("/%s/", $e->getMessage(), automationLibrary::ERR_PROCESS_FAILED_UNEXPECTEDLY);
                        $this->addErrorRecord($e->getMessage(), $_currentRecord, $_process);
                    }
                    $_currentRecordNum ++;
                    
                    // : End
                }
            }
            /**
             * END
             */
            
            // : Tear Down
            $this->_session->element('xpath', "//*[contains(@href,'/logout')]")->click();
            // Wait for page to load and for elements to be present on page
            $e = $w->until(function ($session)
            {
                return $session->element('css selector', 'input[id=identification]');
            });
            $this->assertElementPresent('css selector', 'input[id=identification]');
            
            // : Close connection to database
            $db = null;
            if ($_dbStatus) {
                $this->closeDB();
            }
            // : End
            
            // Terminate session
            $this->_session->close();
            // : End
            
            // : If errors occured. Create xls of entries that failed.
            if (count($this->_error) != 0) {
                $_xlsfilename = $this->_errDir . DIRECTORY_SEPARATOR . date("Y-m-d_His_") . basename(__FILE__, ".php") . ".xlsx";
                $this->writeExcelFile($_xlsfilename, $this->_error, $_xlsColumns);
                if (file_exists($_xlsfilename)) {
                    print("Excel error report written successfully to file: $_xlsfilename");
                } else {
                    print("Excel error report write unsuccessful");
                }
            }
            // : End
        }
    }
    
    // : Private Functions
    
    /**
     * MAXLive_Subcontractors::writeExcelFile($excelFile, $excelData)
     * Create, Write and Save Excel Spreadsheet from collected data obtained from the variance report
     *
     * @param $excelFile, $excelData            
     */
    public function writeExcelFile($excelFile, $excelData, $columns)
    {
        try {
            // Check data validility
            if (count($excelData) != 0) {
                
                // : Create new PHPExcel object
                print("<pre>");
                print(date('H:i:s') . " Create new PHPExcel object" . PHP_EOL);
                $objPHPExcel = new PHPExcel();
                // : End
                
                // : Set properties
                print(date('H:i:s') . " Set properties" . PHP_EOL);
                $objPHPExcel->getProperties()->setCreator(self::XLS_CREATOR);
                $objPHPExcel->getProperties()->setLastModifiedBy(self::XLS_CREATOR);
                $objPHPExcel->getProperties()->setTitle(self::XLS_TITLE);
                $objPHPExcel->getProperties()->setSubject(self::XLS_SUBJECT);
                // : End
                
                // : Setup Workbook Preferences
                print(date('H:i:s') . " Setup workbook preferences" . PHP_EOL);
                $objPHPExcel->getDefaultStyle()
                    ->getFont()
                    ->setName('Arial');
                $objPHPExcel->getDefaultStyle()
                    ->getFont()
                    ->setSize(8);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setFitToWidth(1);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setFitToHeight(0);
                // : End
                
                // : Set Column Headers
                $alphaVar = range('A', 'Z');
                print(date('H:i:s') . " Setup column headers" . PHP_EOL);
                
                $i = 0;
                foreach ($columns as $key) {
                    $objPHPExcel->getActiveSheet()->setCellValue($alphaVar[$i] . "1", $key);
                    $objPHPExcel->getActiveSheet()
                        ->getStyle($alphaVar[$i] . '1')
                        ->getFont()
                        ->setBold(true);
                    $i ++;
                }
                
                // : End
                
                // : Add data from $excelData array
                print(date('H:i:s') . " Add data from error array" . PHP_EOL);
                $rowCount = (int) 2;
                $objPHPExcel->setActiveSheetIndex(0);
                foreach ($excelData as $values) {
                    $i = 0;
                    foreach ($values as $key => $value) {
                        $objPHPExcel->getActiveSheet()
                            ->getCell($alphaVar[$i] . strval($rowCount))
                            ->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                        $i ++;
                    }
                    $rowCount ++;
                }
                // : End
                
                // : Setup Column Widths
                for ($i = 0; $i <= count($columns); $i ++) {
                    $objPHPExcel->getActiveSheet()
                        ->getColumnDimension($alphaVar[$i])
                        ->setAutoSize(true);
                }
                // : End
                
                // : Rename sheet
                print(date('H:i:s') . " Rename sheet" . PHP_EOL);
                $objPHPExcel->getActiveSheet()->setTitle(self::XLS_TITLE);
                // : End
                
                // : Save spreadsheet to Excel 2007 file format
                print(date('H:i:s') . " Write to Excel2007 format" . PHP_EOL);
                print("</pre>" . PHP_EOL);
                $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
                $objWriter->save($excelFile);
                $objPHPExcel->disconnectWorksheets();
                unset($objPHPExcel);
                unset($objWriter);
                // : End
            } else {
                print("<pre>");
                print_r("ERROR: The function was passed an empty array");
                print("</pre>");
                exit();
            }
        } catch (Exception $e) {
            echo "Caught exception: ", $e->getMessage(), "\n";
            exit();
        }
    }

    /**
     * MAXLive_Rates_Create_No_AI::openDB($dsn, $username, $password, $options)
     * Open connection to Database
     *
     * @param string: $dsn            
     * @param string: $username            
     * @param string: $password            
     * @param array: $options            
     */
    private function openDB($dsn, $username, $password, $options)
    {
        try {
            $this->_db = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $ex) {
            return FALSE;
        }
    }

    /**
     * MAXLive_Subcontractors::takeScreenshot()
     * This is a function description for a selenium test function
     *
     * @param object: $_session            
     */
    private function takeScreenshot()
    {
        $_params = func_get_args();
        $_img = $this->_session->screenshot();
        $_data = base64_decode($_img);
        $_pathname_extra = (string) "";
        
        if ($_params && is_array($_params)) {
            if (array_key_exists(0, $_params)) {
                $_pathname_extra = $_params[0];
            }
        }
        
        if ($_pathname_extra) {
            $_file = $this->_scrDir . DIRECTORY_SEPARATOR . date("Y-m-d_His") . "_${_pathname_extra}_WebDriver.png";
        } else {
            $_file = $this->_scrDir . DIRECTORY_SEPARATOR . date("Y-m-d_His") . "_WebDriver.png";
        }
        $_success = file_put_contents($_file, $_data);
        if ($_success) {
            return $_file;
        } else {
            return FALSE;
        }
    }

    /**
     * MAXLive_Rates_Create_No_AI::assertElementPresent($_using, $_value)
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
     * MAXLive_Rates_Create_No_AI::assertElementPresent($_title)
     * This functions switches focus between each of the open windows
     * and looks for the first window where the page title matches
     * the given title and returns true else false
     *
     * @param string: $_title            
     * @param
     *            boolean: return
     */
    private function selectWindow($_title)
    {
        try {
            $_results = (array) array();
            // Store the current window handle value
            $_currentWin = $this->_session->window_handle();
            // Get all open windows handles
            $e = $this->_session->window_handles();
            if (count($e) > 1) {
                foreach ($e as $_browserWindow) {
                    $this->_session->focusWindow($_browserWindow);
                    $_page_title = $this->_session->title();
                    preg_match("/^.+" . $_title . ".+/", $_page_title, $_results);
                    if ((count($_results) != 0) && ($_browserWindow != $_currentWin)) {
                        return true;
                    }
                }
            }
            $this->_session->focusWindow($_currentWin);
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * MAXLive_Rates_Create_No_AI::closeDB()
     * Close connection to Database
     */
    private function closeDB()
    {
        $this->_db = null;
    }

    /**
     * MAXLive_Rates_Create_No_AI::queryDB($sqlquery)
     * Pass MySQL Query to database and return output
     *
     * @param string: $sqlquery            
     * @param array: $result            
     */
    private function queryDB($sqlquery)
    {
        try {
            $result = $this->_db->query($sqlquery);
            return $result->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            return FALSE;
        }
    }

    /**
     * MAXLive_Rates_Create_No_AI::clearWindows()
     * This functions switches focus between each of the open windows
     * and looks for the first window where the page title matches
     * the given title and returns true else false
     *
     * @param object: $this->_session            
     */
    private function clearWindows()
    {
        $_winAll = $this->_session->window_handles();
        $_curWin = $this->_session->window_handle();
        foreach ($_winAll as $_win) {
            if ($_win != $_curWin) {
                $this->_session->focusWindow($_win);
                $this->_session->deleteWindow();
            }
        }
        $this->_session->focusWindow($_curWin);
    }
    
    // : End
}
