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
        $ini = dirname(realpath(__FILE__)) . self::DS . self::INI_DIR . self::DS . self::INI_FILE;
        if (is_file($ini) === FALSE) {
            echo "No " . self::INI_FILE . " file found. Please refer to documentation for script to determine which fields are required and their corresponding values." . PHP_EOL;
            return FALSE;
        }
        $data = parse_ini_file($ini);
        if ((array_key_exists("browser", $data) && $data["browser"]) && (array_key_exists("wdport", $data) && $data["wdport"]) && (array_key_exists("xls", $data) && $data["xls"]) && (array_key_exists("errordir", $data) && $data["errordir"]) && (array_key_exists("screenshotdir", $data) && $data["screenshotdir"]) && (array_key_exists("datadir", $data) && $data["datadir"]) && (array_key_exists("ip", $data) && $data["ip"]) && (array_key_exists("username", $data) && $data["username"]) && (array_key_exists("password", $data) && $data["password"]) && (array_key_exists("welcome", $data) && $data["welcome"]) && (array_key_exists("mode", $data) && $data["mode"])) {
            $this->_username = $data["username"];
            $this->_password = $data["password"];
            $this->_welcome = $data["welcome"];
            $this->_dataDir = $data["datadir"];
            $this->_errDir = $data["errordir"];
            $this->_scrDir = $data["screenshotdir"];
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
        $_dbStatus = FALSE;
        $_match = 0;
        $_process = (string) "";
        $_objectregistry_id = (int) 0;
        
        // Construct full path and filename for csv file using script home dir and data dir path to file
        $_file = dirname(__FILE__) . $this->_dataDir . self::DS . $this->_xls;
        echo $_file . PHP_EOL;
        
        // : Error report columns for the spreadsheet data
        $_xlsColumns = array(
            "Error_Msg",
            "Record Detail",
            "Type"
        );
        
        // : Pull spreadsheet data and store into multi dimensional array
        
        // : Import Rates from spreadsheet
        $_xlsData = new ReadExcelFile($_file, "Rates");
        $_ratesData = $_xlsData->getData();
        unset($_xlsData);
        // : End
        
        // : Import Locations from spreadsheet
        $_xlsData = new ReadExcelFile($_file, "Locations");
        $_locationsData = $_xlsData->getData();
        unset($_xlsData);
        // : End
        
        // : Import Offloading Customers from spreadsheet
        $_xlsData = new ReadExcelFile($_file, "OffloadingCustomers");
        $_offloadingData = $_xlsData->getData();
        unset($_xlsData);
        // : End
        
        // : Import Business Units from spreadsheet
        $_xlsData = new ReadExcelFile($_file, "BusinessUnits");
        $_buData = $_xlsData->getData();
        unset($_xlsData);
        // : End
        
        // : Import Business Units from spreadsheet
        $_xlsData = new ReadExcelFile($_file, "Customer");
        $_customerData = $_xlsData->getData();
        unset($_xlsData);
        // : End
        
        $_data = (array) array();
        
        foreach ($_ratesData as $key => $_value) {
            foreach ($_value as $key2 => $_value2) {
                $_data["rates"][$key2][$key] = $_value2;
            }
        }
        
        foreach ($_locationsData as $key => $_value) {
            foreach ($_value as $key2 => $_value2) {
                $_data["locations"][$key2][$key] = $_value2;
            }
        }
        
        foreach ($_offloadingData as $key => $_value) {
            foreach ($_value as $key2 => $_value2) {
                $_data["offloading"][$key2][$key] = $_value2;
            }
        }
        
        foreach ($_buData as $key => $_value) {
            foreach ($_value as $key2 => $_value2) {
                $_data["bu"][$key2][$key] = $_value2;
            }
        }
        
        foreach ($_customerData as $key => $_value) {
            foreach ($_value as $key2 => $_value2) {
                $_data["customer"][$key2][$key] = $_value2;
            }
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
            $w = new PHPWebDriver_WebDriverWait($this->_session);
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
            if (isset($_data['customer'][1]['tradingName'])) {
                $_sqlquery = preg_replace("/%s/", $_data['customer'][1]['tradingName'], automationLibrary::SQL_QUERY_CUSTOMER);
                $result = $this->queryDB($_sqlquery);
                if (count($result) != 0) {
                    $_customerID = intval($result[0]["ID"]);
                } else {
                    $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                    throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                }
            } else {
                throw Exception(automationLibrary::ERR_NO_CUSTOMER_DATA);
            }
            
            /**
             * END
             */
            
            /**
             * LOCATION SECTION
             */
            
            // If location data exists then continue
            if (array_key_exists('locations', $_data)) {
                
                $_process = "linkCustomerLocations";
                
                foreach ($_data['locations'] as $_locKey => $_locValue) {
                    
                    try {
                        
                        // : Set variables
                        $_locationID = 0;
                        $_customerLocationLinkID = 0;
                        $_currentRecord = "";
                        foreach ($_locValue as $_key1 => $_value1) {
                            $_currentRecord .= "[$_key1]=>$_value1;";
                        }
                        
                        // Get IDs for point and customer link location (if link exists)
                        $_sqlquery = preg_replace("/%n/", $_locValue['pointName'], automationLibrary::SQL_QUERY_LOCATION);
                        $_sqlquery = preg_replace("/%t/", self::_TYPE_POINT, $_sqlquery);
                        $result = $this->queryDB($_sqlquery);
                        if (count($result) != 0) {
                            $_locationID = intval($result[0]["ID"]);
                        } else {
                            $_errmsg = preg_replace("/%s/", $_sqlquery, automationLibrary::ERR_SQL_QUERY_NO_RESULTS_REQ_DATA);
                            throw new Exception($_errmsg . PHP_EOL . "Error occured on line: " . __LINE__);
                        }
                        
                        $_sqlquery = preg_replace("/%l/", $_locValue['pointName'], automationLibrary::SQL_QUERY_CUSTOMER_LOCATION_LINK);
                        $_sqlquery = preg_replace("/%c/", $_sqlquery);
                        $result = $this->queryDB($_sqlquery);
                        if (count($result) != 0) {
                            $_customerLocationLinkID = intval($result[0]["ID"]);
                        }
                        
                        // : If location does exist and link does not exist then create customer location link
                        // Load URL for MAX customers page
                        $this->_session->open($this->_maxurl . automationLibrary::URL_CUSTOMER . $_dataset["customer"]["id"]);
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
                        $this->_session->element("xpath", "//*[@id='udo_CustomerLocations-5__0_location_id-5']/option[text()='" . $_dataValues["value"] . "']")->click();
                        // Select offloading as type for new location from select box
                        $this->_session->element("xpath", "//*[@id='udo_CustomerLocations-8__0_type-8']/option[text()='Offloading']")->click();
                        // Click the submit button
                        $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                        
                        // : Create Business Unit Link for Point Link
                        $myQuery = preg_replace("/%n/", $_dataValues["value"], $this->_myqueries[0]);
                        $myQuery = preg_replace("/%t/", $_dataset["customer"]["value"], $myQuery);
                        $sqlResult = $this->queryDB($myQuery);
                        if (count($sqlResult) != 0) {
                            $_process = "create customer location business unit link";
                            $_dataset[$_dataKey]["link"] = $sqlResult[0]["ID"];
                            // Load URL for the customer location business unit databrowser page
                            $this->_session->open($this->_maxurl . automationLibrary::URL_CUST_LOCATION_BU . $_dataset[$_dataKey]["link"]);
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
                            
                            $this->_session->element("xpath", "//*[@id='udo_CustomerLocationsBusinessUnit_link-2__0_businessUnit_id-2']/option[text()='" . $_dataset["business unit"]["value"] . "']")->click();
                            // Click the submit button
                            $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                            // Wait for element
                            $e = $w->until(function ($session)
                            {
                                return $session->element("css selector", "#button-create");
                            });
                        } else {
                            throw new Exception("Could not find customer location record: " . $_dataValues["value"]);
                        }
                        // : End
                        
                        // : End
                    } catch (Exception $e) {
                        $this->addErrorRecord($e->getMessage(), $_currentRecord, $_process);
                    }
                    
                    // : Link Locations
                    // : End
                }
            }
            /**
             * END
             */
            
            /**
             * OFFLOADING CUSTOMER SECTION
             */
            
            // If offloading customer data exists then continue
            if ($_offloadingData) {
                
                // Get offloading customer ID
                $myQuery = "select ID from udo_customer where tradingName='" . $_dataset["offloading customer"]["value"] . "' and primaryCustomer = 0 and useFandVContract = 0 and active = 1;";
                $sqlResultA = $this->queryDB($myQuery);
                if (count($sqlResultA) != 0) {
                    $_dataset["offloading customer"]["id"] = intval($sqlResultA[0]["ID"]);
                    
                    // : If offloading customer does exist then check if offloading customer is linked to the customer and store the link ID
                    $myQuery = preg_replace("/%o/", $_dataset["offloading customer"]["value"], $this->_myqueries[1]);
                    $myQuery = preg_replace("/%t/", $_dataset["customer"]["value"], $myQuery);
                    $sqlResultB = $this->queryDB($myQuery);
                    if (count($sqlResultB) != 0) {
                        $_dataset["offloading customer"]["link"] = intval($sqlResultB[0]["ID"]);
                    } else {
                        $_dataset["offloading customer"]["link"] = NULL;
                    }
                    // : End
                } else {
                    $_dataset["offloading customer"]["id"] = NULL;
                }
                
                // : Create Offloading Customer
                // : End
                
                // : Link Offloading Customer
                // : End
                
                // : Check offloading customer exists
                
                try {
                    
                    // : Create and link Offloading Customer
                    if ((! $_dataset["offloading customer"]["id"]) || (! $_dataset["offloading customer"]["link"])) {
                        
                        $_process = "create offloading customer process begin";
                        $_winAll = $this->_session->window_handles();
                        // Set window focus to main window
                        $this->_session->focusWindow($_winAll[0]);
                        // If there is more than 1 window open then close all but main window
                        if (count($_winAll) > 1) {
                            $this->clearWindows();
                        }
                        // : Load customer data browser page for Customer
                        $this->_session->open($this->_maxurl . automationLibrary::URL_CUSTOMER . $_dataset["customer"]["id"]);
                        
                        // Wait for element = Page heading
                        $e = $w->until(function ($session)
                        {
                            return $session->element("css selector", "#subtabselector");
                        });
                        $this->_session->element("xpath", "//*[@id='subtabselector']/select/option[14]")->click();
                        
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
                        
                        if (! $_dataset["offloading customer"]["id"]) {
                            try {
                                $_process = "create offloading customer";
                                $this->assertElementPresent("link text", "Create Customer");
                                $this->_session->element("link text", "Create Customer")->click();
                                
                                // Select New Window
                                $_winAll = $this->_session->window_handles();
                                if (count($_winAll > 1)) {
                                    $this->_session->focusWindow($_winAll[1]);
                                } else {
                                    throw new Exception("ERROR: Window not present");
                                }
                                
                                // Wait for element = Page heading
                                $e = $w->until(function ($session)
                                {
                                    return $session->element("xpath", "//*[@id='udo_Customer-22_0_0_tradingName-22']");
                                });
                                
                                $this->assertElementPresent("xpath", "//*[@id='udo_Customer-22_0_0_tradingName-22']");
                                $this->assertElementPresent("xpath", "//*[@id='udo_Customer-12_0_0_legalName-12']");
                                $this->assertElementPresent("xpath", "//*[@id='udo_Customer-33_0_0_customerType_id-33[11]']");
                                $this->assertElementPresent("xpath", "//*[@id='checkbox_udo_Customer-2_0_0_active-2']");
                                $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                
                                $this->_session->element("xpath", "//*[@id='udo_Customer-22_0_0_tradingName-22']")->sendKeys($_dataset["offloading customer"]["value"]);
                                $this->_session->element("xpath", "//*[@id='udo_Customer-12_0_0_legalName-12']")->sendKeys($_dataset["offloading customer"]["value"]);
                                $this->_session->element("xpath", "//*[@id='udo_Customer-33_0_0_customerType_id-33[11]']")->click();
                                $this->_session->element("xpath", "//*[@id='checkbox_udo_Customer-2_0_0_active-2']")->click();
                                $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                            } catch (Exception $e) {
                                $_erCount = count($this->_error);
                                $this->_error[$_erCount + 1]["error"] = $e->getMessage();
                                $this->_error[$_erCount + 1]["record"] = $this->lastRecord . ". Object data that failed: " . $_dataset["offloading customer"]["value"];
                                $this->_error[$_erCount + 1]["type"] = $_process;
                            }
                            if (count($_winAll > 1)) {
                                $this->_session->focusWindow($_winAll[0]);
                            }
                            
                            // Wait for element = Page heading
                            $e = $w->until(function ($session)
                            {
                                return $session->element("xpath", "//*[contains(text(),'Capture the details of Offloading Customers')]");
                            });
                        }
                        $_process = "complete create of offloading customer";
                        $this->assertElementPresent("xpath", "//*[@id='udo_OffloadingCustomers-3__0_customer_id-3']");
                        $this->assertElementPresent("xpath", "//*[@id='udo_OffloadingCustomers-6__0_offloadingCustomer_id-6']");
                        $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                        
                        $this->_session->element("xpath", "//*[@id='udo_OffloadingCustomers-3__0_customer_id-3']/option[text()='" . $_dataset["customer"]["value"] . "']")->click();
                        $this->_session->element("xpath", "//*[@id='udo_OffloadingCustomers-6__0_offloadingCustomer_id-6']/option[text()='" . $_dataset["offloading customer"]["value"] . "']")->click();
                        $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                        
                        // : Create Business Unit Link for Offloading Customer Link
                        $myQuery = preg_replace("/%o/", $_dataset["offloading customer"]["value"], $this->_myqueries[1]);
                        $myQuery = preg_replace("/%t/", $_dataset["customer"]["value"], $myQuery);
                        $sqlResult = $this->queryDB($myQuery);
                        if (count($sqlResult) != 0) {
                            $_process = "create business unit link for offloading customer link";
                            $_dataset["offloading customer"]["link"] = $sqlResult[0]["ID"];
                            $this->_session->open($this->_maxurl . automationLibrary::URL_OFFLOAD_CUST_BU . $_dataset["offloading customer"]["link"]);
                            
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
                            
                            $this->_session->element("xpath", "//*[@id='udo_OffloadingCustomersBusinessUnit_link-2__0_businessUnit_id-2']/option[text()='" . $_dataset["business unit"]["value"] . "']")->click();
                            $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                            
                            // Wait for element = #button-create
                            $e = $w->until(function ($session)
                            {
                                return $session->element("xpath", "//div[@id='button-create']");
                            });
                        } else {
                            throw new Exception("Could not find offloading customer record: " . $_dataset["offloading customer"]["value"]);
                        }
                    }
                } catch (Exception $e) {
                    $_erCount = count($this->_error);
                    $this->_error[$_erCount + 1]["error"] = $e->getMessage();
                    $this->_error[$_erCount + 1]["record"] = $this->lastRecord . ". Object data that failed: " . $_dataset["offloading customer"]["value"];
                    $this->_error[$_erCount + 1]["type"] = $_process;
                }
                // : End
            }
            
            /**
             * END
             */
            
            /**
             * ROUTE AND RATE SECTION
             */
            
            // If rates data exists then continue
            if ($_ratesData) {
                
                // : Create Route
                
                // Get customer ID
                $myQuery = "select ID from udo_customer where tradingName='" . $_dataset["customer"]["value"] . "' and primaryCustomer = 1 and useFandVContract = 0 and active = 1;";
                $result = $this->queryDB($myQuery);
                if (count($result) != 0) {
                    $_dataset["customer"]["id"] = intval($result[0]["ID"]);
                } else {
                    throw new Exception("Error: Customer not found. Please check and amend customer name.");
                }
                
                // Get truck description ID
                $myQuery = "select ID from udo_truckdescription where description='" . $_dataset["truck type"]["value"] . "';";
                $result = $this->queryDB($myQuery);
                if (count($result) != 0) {
                    $_dataset["truck type"]["id"] = intval($result[0]["ID"]);
                } else {
                    throw new Exception("Error: Truck description not found. Please check and amend truck description.");
                }
                
                // Get rate type ID
                $myQuery = "select ID from udo_ratetype where name='" . $_dataset["rate type"]["value"] . "';";
                $result = $this->queryDB($myQuery);
                if (count($result) != 0) {
                    $_dataset["rate type"]["id"] = intval($result[0]["ID"]);
                } else {
                    throw new Exception("Error: Rate type not found. Please check and amend rate type name.");
                }
                
                // Get business unit ID
                $myQuery = "select ID from udo_businessunit where name='" . $_dataset["business unit"]["value"] . "';";
                $result = $this->queryDB($myQuery);
                if (count($result) != 0) {
                    $_dataset["business unit"]["id"] = intval($result[0]["ID"]);
                } else {
                    throw new Exception("Error: Business unit not found. Please check and amend business unit name.");
                }
                
                // : Check if route and rate exists
                
                // Check and store route ID if exists
                if (($_dataset["location from town"]["id"] != FALSE) && ($_dataset["location to town"]["id"] != FALSE)) {
                    $myQuery = preg_replace("@%f@", $_dataset["location from town"]["value"], $this->_myqueries[7]);
                    $myQuery = preg_replace("@%t@", $_dataset["location to town"]["value"], $myQuery);
                    $sqlResult = $this->queryDB($myQuery);
                    if (count($sqlResult) != 0) {
                        $_dataset["rate"]["other"] = $sqlResult[0]["ID"];
                    } else {
                        $_dataset["rate"]["other"] = NULL;
                    }
                }
                
                // Check and store rate ID if exists
                if (($_dataset["rate"]["other"] != FALSE)) {
                    // "select ID from udo_rates where route_id = %ro and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and model='%m' and businessUnit_id=%b and rateType_id=%ra;",
                    $myQuery = preg_replace("@%ro@", $_dataset["rate"]["other"], $this->_myqueries[8]);
                    $myQuery = preg_replace("@%ra@", $_dataset["rate type"]["id"], $myQuery);
                    $myQuery = preg_replace("@%g@", $objectregistry_id, $myQuery);
                    $myQuery = preg_replace("@%c@", $_dataset["customer"]["id"], $myQuery);
                    $myQuery = preg_replace("@%d@", $_dataset["truck type"]["id"], $myQuery);
                    $myQuery = preg_replace("@%m@", $_dataset["contribution model"]["value"], $myQuery);
                    $myQuery = preg_replace("@%b@", $_dataset["business unit"]["id"], $myQuery);
                    $sqlResult = $this->queryDB($myQuery);
                    if (count($sqlResult) != 0) {
                        $_dataset["rate"]["id"] = $sqlResult[0]["ID"];
                    } else {
                        $_dataset["rate"]["id"] = NULL;
                    }
                }
                
                // : End
                
                // : End
                
                // : Create Rate
                // : End
                
                // : Check if route and rate exists for customer and create route and rate if they dont exist
                
                try {
                    
                    if ((! $_dataset["rate"]["id"])) {
                        
                        // : If route does not exist from previous check, check again and store route ID if it exists
                        if (($_dataset["location from town"]["id"] != FALSE) && ($_dataset["location to town"]["id"] != FALSE) && (! $_dataset["rate"]["other"])) {
                            $_process = "check if route exists";
                            $myQuery = preg_replace("@%f@", $_dataset["location from town"]["value"], $this->_myqueries[7]);
                            $myQuery = preg_replace("@%t@", $_dataset["location to town"]["value"], $myQuery);
                            $sqlResult = $this->queryDB($myQuery);
                            if (count($sqlResult) != 0) {
                                $_dataset["rate"]["other"] = $sqlResult[0]["ID"];
                            } else {
                                $_dataset["rate"]["other"] = NULL;
                            }
                        }
                        // : End
                        
                        // Concatenate string for route name
                        $_routeName = $_dataset["location from town"]["value"] . " TO " . $_dataset["location to town"]["value"];
                        
                        $_process = "begin create rate process";
                        // Get all currently open windows
                        $_winAll = $this->_session->window_handles();
                        // Set window focus to main window
                        $this->_session->focusWindow($_winAll[0]);
                        // If there is more than 1 window open then close all but main window
                        if (count($_winAll) > 1) {
                            $this->clearWindows();
                        }
                        
                        // Load the MAX customer page
                        $this->_session->open($this->_maxurl . automationLibrary::URL_CUSTOMER . $_dataset["customer"]["id"]);
                        
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
                        
                        // : Create route if it does not exist
                        if (! $_dataset["rate"]["other"]) {
                            try {
                                $_process = "create route";
                                
                                // Assert element on page - link: Create Route
                                $this->assertElementPresent("link text", "Create Route");
                                // Click element - link: Create Route
                                $this->_session->element("link text", "Create Route")->click();
                                // Select New Window
                                $_allWin = $this->_session->window_handles();
                                if (count($_allWin > 1)) {
                                    $this->_session->focusWindow($_allWin[1]);
                                } else {
                                    throw new Exception("ERROR: Window not present.");
                                }
                                // Wait for element Page Heading
                                $e = $w->until(function ($session)
                                {
                                    return $session->element("xpath", "//*[@name='udo_Route[0][locationFrom_id]']");
                                });
                                
                                // : Assert all elements on page
                                $this->assertElementPresent("xpath", "//*[@name='udo_Route[0][locationTo_id]']");
                                $this->assertElementPresent("xpath", "//*[@name='udo_Route[0][expectedKms]']");
                                $this->assertElementPresent("xpath", "//*[@name='udo_Route[0][duration]']");
                                $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                // : End
                                
                                try {
                                    $this->_session->element("xpath", "//*[@name='udo_Route[0][locationFrom_id]']/option[text()='" . $_dataset["location from town"]["value"] . "']")->click();
                                } catch (PHPWebDriver_NoSuchElementWebDriverError $e) {
                                    throw new Exception("ERROR: Could not find the location from on the create route page" . PHP_EOL . $e->getMessage());
                                }
                                
                                $this->_session->element("xpath", "//*[@name='udo_Route[0][locationTo_id]']/option[text()='" . $_dataset["location to town"]["value"] . "']")->click();
                                if ($_dataset["expected kms"]["value"] != FALSE) {
                                    $this->_session->element("xpath", "//*[@name='udo_Route[0][expectedKms]']")->sendKeys($_dataset["expected kms"]["value"]);
                                    // Calculate duration from kms value at 60K/H
                                    $duration = strval(number_format((floatval($_dataset["expected kms"]["value"]) / 80) * 60, 0, "", ""));
                                    $this->_session->element("xpath", "//*[@name='udo_Route[0][duration]']")->sendKeys($duration);
                                }
                                $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                            } catch (Exception $e) {
                                $_erCount = count($this->_error);
                                $this->_error[$_erCount + 1]["error"] = $e->getMessage();
                                $this->_error[$_erCount + 1]["record"] = $this->lastRecord;
                                $this->_error[$_erCount + 1]["type"] = $_process;
                            }
                            
                            if (count($_allWin > 1)) {
                                $this->_session->focusWindow($_allWin[0]);
                            }
                            
                            // Wait for element Page Heading
                            $e = $w->until(function ($session)
                            {
                                return $session->element("xpath", "//*[@id='udo_Rates-31__0_route_id-31']");
                            });
                        }
                        // : End
                        $_process = "create rate";
                        $this->assertElementPresent("xpath", "//*[@id='udo_Rates-30__0_rateType_id-30']");
                        $this->assertElementPresent("xpath", "//*[@id='udo_Rates-4__0_businessUnit_id-4']");
                        $this->assertElementPresent("xpath", "//*[@id='udo_Rates-36__0_truckDescription_id-36']");
                        $this->assertElementPresent("xpath", "//*[@id='udo_Rates-20__0_model-20']");
                        $this->assertElementPresent("xpath", "//*[@id='checkbox_udo_Rates-15_0_0_enabled-15']");
                        $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                        
                        $this->_session->element("xpath", "//*[@id='udo_Rates-31__0_route_id-31']/option[text()='" . $_routeName . "']")->click();
                        $this->_session->element("xpath", "//*[@id='udo_Rates-30__0_rateType_id-30']/option[text()='" . $_dataset["rate type"]["value"] . "']")->click();
                        $this->_session->element("xpath", "//*[@id='udo_Rates-4__0_businessUnit_id-4']/option[text()='" . $_dataset["business unit"]["value"] . "']")->click();
                        $this->_session->element("xpath", "//*[@id='udo_Rates-36__0_truckDescription_id-36']/option[text()='" . $_dataset["truck type"]["value"] . "']")->click();
                        $this->_session->element("xpath", "//*[@id='udo_Rates-20__0_model-20']/option[text()='" . $_dataset["contribution model"]["value"] . "']")->click();
                        $this->_session->element("xpath", "//*[@id='checkbox_udo_Rates-15_0_0_enabled-15']")->click();
                        $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                        
                        // Wait for element = #button-create
                        try {
                            $e = $w->until(function ($session)
                            {
                                return $session->element("css selector", "#button-create");
                            });
                        } catch (PHPWebDriver_NoSuchElementWebDriverError $e) {
                            throw new Exception("ERROR: Could not find the create button after post of create new rate process." . PHP_EOL . $e->getMessage());
                        }
                    }
                    // : End
                    
                    // : Check if route and rate exists
                    
                    // Check and store route ID if exists
                    if (($_dataset["location from town"]["id"] != FALSE) && ($_dataset["location to town"]["id"] != FALSE)) {
                        $myQuery = preg_replace("@%f@", $_dataset["location from town"]["value"], $this->_myqueries[7]);
                        $myQuery = preg_replace("@%t@", $_dataset["location to town"]["value"], $myQuery);
                        $sqlResult = $this->queryDB($myQuery);
                        if (count($sqlResult) != 0) {
                            $_dataset["rate"]["other"] = $sqlResult[0]["ID"];
                        } else {
                            $_dataset["rate"]["other"] = NULL;
                        }
                    }
                    
                    // Check and store rate ID if exists
                    if (($_dataset["rate"]["other"] != FALSE)) {
                        // "select ID from udo_rates where route_id = %ro and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and model='%m' and businessUnit_id=%b and rateType_id=%ra;",
                        $myQuery = preg_replace("@%ro@", $_dataset["rate"]["other"], $this->_myqueries[8]);
                        $myQuery = preg_replace("@%ra@", $_dataset["rate type"]["id"], $myQuery);
                        $myQuery = preg_replace("@%g@", $objectregistry_id, $myQuery);
                        $myQuery = preg_replace("@%c@", $_dataset["customer"]["id"], $myQuery);
                        $myQuery = preg_replace("@%d@", $_dataset["truck type"]["id"], $myQuery);
                        $myQuery = preg_replace("@%m@", $_dataset["contribution model"]["value"], $myQuery);
                        $myQuery = preg_replace("@%b@", $_dataset["business unit"]["id"], $myQuery);
                        $sqlResult = $this->queryDB($myQuery);
                        
                        if (count($sqlResult) != 0) {
                            $_dataset["rate"]["id"] = $sqlResult[0]["ID"];
                        } else {
                            $_dataset["rate"]["id"] = NULL;
                        }
                    }
                    // : End
                    $_dateRangeValues = array();
                    $_dateRangeValues["Rate"] = $_dataset["rate"]["value"];
                    $_dateRangeValues["DaysPerMonth"] = $_dataset["days per month"]["value"];
                    $_dateRangeValues["DaysPerTrip"] = $_dataset["days per trip"]["value"];
                    $_dateRangeValues["ExpectedDistance"] = $_dataset["expected kms"]["value"];
                    $_dateRangeValues["ExpectedEmptyKms"] = $_dataset["expected empty kms"]["value"];
                    $_dateRangeValues["FuelConsumptionForRoute"] = $_dataset["fuel consumption"]["value"];
                    $_dateRangeValues["Fleet"] = $_dataset["fleet value"]["value"];
                    
                    // : Create rate value for route and rate
                    foreach ($_dateRangeValues as $_drvKey => $_drvValue) {
                        $_process = "create date range values for route and rate";
                        if (($_dataset["rate"]["id"]) && ($_dataset["rate"]["other"]) && ($_drvValue)) {
                            // Prepare url string to load next
                            $rateurl = preg_replace("/%s/", $_dataset["rate"]["id"], $this->_maxurl . automationLibrary::URL_RATEVAL);
                            // Load URL for route and rate databrowser page
                            $this->_session->open($rateurl);
                            
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
                        } else {
                            throw new Exception("ERROR: Could not find newly created rate record.");
                        }
                    }
                    // : End
                } catch (Exception $e) {
                    $_erCount = count($this->_error);
                    $this->_error[$_erCount + 1]["error"] = $e->getMessage();
                    $this->_error[$_erCount + 1]["record"] = $this->lastRecord;
                    $this->_error[$_erCount + 1]["type"] = $_process;
                }
                
                // : End
            }
            /**
             * END
             */
            
            // : Exception handling - add to sections above to capture errors into errors array
            try {} catch (Exception $e) {
                echo "Error: " . $e->getMessage() . PHP_EOL;
                echo "Time of error: " . date("Y-m-d H:i:s") . PHP_EOL;
                echo "Last record: " . $this->lastRecord;
                $this->takeScreenshot();
                $_erCount = count($this->_error);
                $this->_error[$_erCount + 1]["error"] = $e->getMessage();
                $this->_error[$_erCount + 1]["record"] = $this->lastRecord;
                $this->_error[$_erCount + 1]["type"] = "Route and Rate";
            }
            // : End
            
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
                $_xlsfilename = (dirname(__FILE__) . $this->_errDir . self::DS . date("Y-m-d_His_") . "MAXLive_Rates_Create_Script" . ".xlsx");
                $this->writeExcelFile($_xlsfilename, $this->_error, $_xlsColumns);
                if (file_exists($_xlsfilename)) {
                    print("Excel error report written successfully to file: $_xlsfilename");
                } else {
                    print("Excel error report write unsuccessful");
                }
            }
            // : End
        } else {
            throw new Exception($this->getFunctionErrorMsg());
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
        $_img = $this->_session->screenshot();
        $_data = base64_decode($_img);
        $_file = dirname(__FILE__) . $this->_scrDir . DIRECTORY_SEPARATOR . date("Y-m-d_His") . "_WebDriver.png";
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

    /**
     * MAXLive_Rates_Create_No_AI::ImportCSVFileIntoArray($csvFile)
     * From supplied csv file save data into multidimensional array
     *
     * @param string: $csvFile            
     * @param array: $_result            
     */
    private function ImportCSVFileIntoArray($csvFile)
    {
        try {
            $_data = (array) array();
            $_header = NULL;
            if (file_exists($csvFile)) {
                if (($_handle = fopen($csvFile, 'r')) !== FALSE) {
                    while (($_row = fgetcsv($_handle, self::CSV_LIMIT, self::DELIMITER, self::ENCLOSURE)) !== FALSE) {
                        if (! $_header) {
                            foreach ($_row as $_value) {
                                $_header[] = strtolower($_value);
                            }
                        } else {
                            $_data[] = array_combine($_header, $_row);
                        }
                    }
                    fclose($_handle);
                    
                    if (count($_data) != 0) {
                        
                        foreach ($_data as $_key => $_value) {
                            foreach ($_value as $_keyA => $_valueA) {
                                $_data[$_key][$_keyA] = $this->stringHypenFix($_valueA);
                            }
                        }
                        
                        return $_data;
                    } else {
                        $_msg = preg_replace("@%s@", $csvFile, self::FILE_EMPTY);
                        throw new Exception($_msg);
                    }
                } else {
                    $_msg = preg_replace("@%s@", $csvFile, self::COULD_NOT_OPEN_FILE);
                    throw new Exception($_msg);
                }
            } else {
                $_msg = preg_replace("@%s@", $csvFile, self::FILE_NOT_FOUND);
                throw new Exception($_msg);
            }
        } catch (Exception $e) {
            $this->_functionError = $e->getMessage();
            return FALSE;
        }
    }
    
    // : End
}