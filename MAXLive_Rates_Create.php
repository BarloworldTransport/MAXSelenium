<?php
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
include_once 'PHPUnit/Extensions/PHPExcel/Classes/PHPExcel.php';
include_once 'AutomationLibrary.php';
include_once 'MAX_Routes_Rates.php';

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

    const PB_URL = "/Planningboard";

    const POINT_URL = "/Country_Tab/points?&tab_id=52";

    const CITY_URL = "/Country_Tab/cities?&tab_id=50";

    const CUSTOMER_URL = "/DataBrowser?browsePrimaryObject=461&browsePrimaryInstance=";

    const LOCATION_BU_URL = "/DataBrowser?browsePrimaryObject=495&browsePrimaryInstance=";

    const OFF_CUST_BU_URL = "/DataBrowser?browsePrimaryObject=494&browsePrimaryInstance=";

    const RATEVAL_URL = "/DataBrowser?&browsePrimaryObject=udo_Rates&browsePrimaryInstance=%s&browseSecondaryObject=DateRangeValue&relationshipType=Rate";

    const DS = DIRECTORY_SEPARATOR;

    const BF = "0.00";

    const COUNTRY = "South Africa";

    const LIVE_URL = "https://login.max.bwtsgroup.com";

    const TEST_URL = "http://max.mobilize.biz";

    const INI_FILE = "rates_data.ini";

    const INI_DIR = "ini";

    const XLS_CREATOR = "MAXLive_Rates_Create.php";

    const XLS_TITLE = "Error Report";

    const XLS_SUBJECT = "Errors caught while creating rates for subcontracts";

    const DELIMITER = ',';

    const ENCLOSURE = '"';

    const CSV_LIMIT = 0;
    
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

    protected $_version;

    protected $_dataDir;

    protected $_errDir;

    protected $_scrDir;

    protected $_wdport;

    protected $_browser;

    protected $_ip;

    protected $_proxyip;

    protected $_csv;

    protected $_ratesonly;

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

    protected $_myqueries = array(
        "select ID from udo_customerlocations where location_id IN (select ID from udo_location where name='%n' and _type='udo_Point') and customer_id IN (select ID from udo_customer where tradingName='%t');",
        "select ID from udo_offloadingcustomers where offloadingCustomer_id IN (select ID from udo_customer where tradingName='%o') and customer_id IN (select ID from udo_customer where tradingName='%t');",
        "select ID from udo_customer where tradingName='%t';",
        "select ID from udo_rates where route_id IN (select ID from udo_route where locationFrom_id IN (select ID from udo_location where name='%f') and locationTo_id IN (select ID from udo_location where name='%t')) and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and model='%m' and businessUnit_id=%b and rateType_id=%r;",
        "select ID from objectregistry where handle = 'udo_Customer';",
        "select ID from udo_location where name='%s' and _type='%t';",
        "select ID from udo_zone where name='%s';",
        "select ID from udo_route where locationFrom_id IN (select ID from udo_location where name='%f') and locationTo_id IN (select ID from udo_location where name='%t');",
        "select ID from udo_rates where route_id=%ro and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and model='%m' and businessUnit_id=%b and rateType_id=%ra;"
    );
    
    // : Public functions
    // : Getters
    
    /**
     * MAXLive_Rates_Create::getFunctionErrorMsg()
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
    // : End
    
    /**
     * MAXLive_Rates_Create::stringHypenFix($_value)
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
     * MAXLive_Rates_Create::__construct()
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
        if ((array_key_exists("ratesonly", $data) && $data["ratesonly"]) && (array_key_exists("version", $data) && $data["version"]) && (array_key_exists("browser", $data) && $data["browser"]) && (array_key_exists("wdport", $data) && $data["wdport"]) && (array_key_exists("csv", $data) && $data["csv"]) && (array_key_exists("errordir", $data) && $data["errordir"]) && (array_key_exists("screenshotdir", $data) && $data["screenshotdir"]) && (array_key_exists("datadir", $data) && $data["datadir"]) && (array_key_exists("ip", $data) && $data["ip"]) && (array_key_exists("username", $data) && $data["username"]) && (array_key_exists("password", $data) && $data["password"]) && (array_key_exists("welcome", $data) && $data["welcome"]) && (array_key_exists("mode", $data) && $data["mode"])) {
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
            $this->_csv = $data["csv"];
            $this->_version = $data["version"];
            $this->_ratesonly = $data["ratesonly"];
            
            // Determine MAX URL to be used for this test run
            $this->_maxurl = AutomationLibrary::getMAXURL($this->_mode, $this->_version);
        } else {
            echo "The correct data is not present in " . self::INI_FILE . ". Please confirm. Fields are username, password, welcome and mode" . PHP_EOL;
            return FALSE;
        }
    }

    /**
     * MAXLive_Rates_Create::__destruct()
     * Class destructor
     * Allow for garbage collection
     */
    public function __destruct()
    {
        unset($this);
    }
    // : End
    
    /**
     * MAXLive_Rates_Create::setUp()
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
     * MAXLive_Rates_Create::testCreateContracts()
     * Pull F and V Contract data and automate creation of F and V Contracts
     */
    public function testCreateRates()
    {
        
        // : Define local variables
        $_dbStatus = FALSE;
        $_match = 0;
        $_process = (string) "";
        $_objectregistry_id = (int) 0;
        // Define array of keywords to validate data headers
        $_headers = (array) array(
            "customer",
            "offloading customer",
            "province from",
            "province to",
            "country from",
            "country to",
            "location from town",
            "location to town",
            "location from point",
            "location to point",
            "rate type",
            "rate",
            "expected kms",
            "expected empty kms",
            "days per trip",
            "days per month",
            "truck type",
            "lead kms",
            "minimum tons",
            "contribution model",
            "business unit",
            "zone from",
            "zone to",
            "fleet value",
            "fuel consumption",
            "start date"
        );
        
        $_keys = array(
            "id" => 0,
            "value" => "",
            "link" => 0,
            "bulink" => 0,
            "other" => ""
        );
        
        // : Error report columns for the spreadsheet data
        $_xlsColumns = array(
            "Error_Msg",
            "Record Detail",
            "Type"
        );
        
        $_dataset = array_fill_keys($_headers, $_keys);
        $_colcount = (int) count($_headers);
        $_columns = (array) array();
        // : End
        
        // Construct full path and filename for csv file using script home dir and data dir path to file
        $_file = realpath($this->_dataDir) . self::DS . $this->_csv;
        
        /*
         * Will be used at a later stage to make validation more dynamic $_headers = ( array ) array ( 0 => array("customer", "!offload"), 1 => array("offload", "customer"), 2 => array("location", "from", "town"), 3 => array("location", "from", "point"), 4 => array("location","to","town"), 5 => array("location","to","point"), 6 => array("rate","type"), 7 => array("rate"), 8 => array("expected","kms"), 9 => array("expected","empty","kms"), 10 => array("days","per","trip"), 11 => array("truck","type"), 12 => array("lead", "kms"), 13 => array("minimum","tons"), 14 => array("contrib","model"), 15 => array("business","unit") );
         */
        
        // Import data from file and if successful continue script else terminate script and return error
        if (($_data = $this->importCSVFileIntoArray($_file)) !== FALSE) {
            
            // : Get column headers from imported data and validate that headers are all present
            $_columns = array_keys($_data[1], $_headers);
            foreach ($_data[0] as $_key => $_value) {
                $_columns[] = $_key;
                $_searchKey = "/^" . strtolower(preg_replace("@\s@", "\s", $_key) . "$/");
                $_result = preg_grep($_searchKey, $_headers);
                if (count($_result) != 0) {
                    $_match ++;
                }
            }
            ;
            // : End
            
            if ($_match === $_colcount) {
                
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
                
                $_autoLib = new AutomationLibrary($this->_session, $this, $w, $this->_mode, $this->_version);
                
                // Create object for MAX LoginLogout class
                $_maxLoginLogout = new maxLoginLogout($_autoLib, $this->_maxurl);
                
                // Create object for MAX Route and Rates class
                $_maxRouteRate = new maxRoutesRates($_autoLib, $this->_maxurl);
                
                // Log into MAX
                if (! $_maxLoginLogout->maxLogin($this->_username, $this->_password, $this->_welcome)) {
                    throw new Exception("Error: Failed to log into MAX." . PHP_EOL . $e->getMessage());
                }
                // : End
                
                // : Get objectregistry_id for udo_Customer
                $myQuery = $this->_myqueries[4];
                $result = $this->queryDB($myQuery);
                if (count($result) != 0) {
                    $objectregistry_id = intval($result[0]["ID"]);
                } else {
                    throw new Exception("Error: Object registry record for udo_customer not found.");
                }
                // : End
                
                // : Load Planningboard to rid of iframe loading on every page from here on
                $this->_session->open($this->_maxurl . self::PB_URL);
                $e = $w->until(function ($session)
                {
                    return $session->element("xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]");
                });
                // : End
                // : Main loop - Run through each row imported from CSV file
                foreach ($_data as $_value) {
                    
                    try {
                        // : Prepare variables
                        foreach ($_dataset as $_aKey => $_aValue) {
                            $_dataset[$_aKey]["value"] = $_value[$_aKey];
                        }
                        // : End
                        
                        $this->lastRecord = "Customer: " . $_dataset["customer"]["value"] . ", Route: " . $_dataset["location from town"]["value"] . " TO " . $_dataset["location to town"]["value"] . ", Rate Value: " . $_dataset["rate"]["value"] . ", Truck Description: " . $_dataset["truck type"]["value"];
                        
                        // Get truck description ID
                        $myQuery = "select ID from udo_truckdescription where description='" . $_dataset["truck type"]["value"] . "';";
                        $result = $this->queryDB($myQuery);
                        if (count($result) != 0) {
                            $_dataset["truck type"]["id"] = intval($result[0]["ID"]);
                        } else {
                            throw new Exception("Error: Truck description not found. Please check and amend truck description.");
                        }
                        
                        // Get customer ID
                        $myQuery = "select ID from udo_customer where tradingName='" . $_dataset["customer"]["value"] . "' and primaryCustomer = 1 and useFandVContract = 0 and active = 1;";
                        $result = $this->queryDB($myQuery);
                        if (count($result) != 0) {
                            $_dataset["customer"]["id"] = intval($result[0]["ID"]);
                        } else {
                            throw new Exception("Error: Customer not found. Please check and amend customer name.");
                        }
                        
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
                        
                        // Get IDs for all locations
                        $_queries = array();
                        switch ($_dataset["business unit"]["value"]) {
                            case "Timber 24":
                                {
                                    $_queries["location from town"] = "select ID from udo_location where name='" . $_dataset["location from town"]["value"] . "' and _type='udo_Plantation' and active=1;";
                                    $_queries["location from point"] = "select ID from udo_location where name='" . $_dataset["location from point"]["value"] . "' and _type='udo_Depot' and active=1;";
                                    $_queries["location to town"] = "select ID from udo_location where name='" . $_dataset["location to town"]["value"] . "' and _type='udo_City' and active=1;";
                                    $_queries["location to point"] = "select ID from udo_location where name='" . $_dataset["location to point"]["value"] . "' and _type='udo_Mill' and active=1;";
                                    $_queries["province from"] = "select ID from udo_location where name='" . $_dataset["province from"]["value"] . "' and _type='udo_Province' and active=1;";
                                    $_queries["province to"] = "select ID from udo_location where name='" . $_dataset["province to"]["value"] . "' and _type='udo_Province' and active=1;";
                                    break;
                                }
                            default:
                                {
                                    $_queries["location from town"] = "select ID from udo_location where name='" . $_dataset["location from town"]["value"] . "' and _type='udo_City' and active=1;";
                                    $_queries["location from point"] = "select ID from udo_location where name='" . $_dataset["location from point"]["value"] . "' and _type='udo_Point' and active=1;";
                                    $_queries["location to town"] = "select ID from udo_location where name='" . $_dataset["location to town"]["value"] . "' and _type='udo_City' and active=1;";
                                    $_queries["location to point"] = "select ID from udo_location where name='" . $_dataset["location to point"]["value"] . "' and _type='udo_Point' and active=1;";
                                    
                                    if ($_dataset["country from"]["value"] == "South Africa") {
                                        // If country is South Africa capture Province as parent
                                        $_queries["province from"] = "select ID from udo_location where name='" . $_dataset["province from"]["value"] . "' and _type='udo_Province' and active=1;";
                                    } else {
                                        // If country is not South Africa capture Province as country
                                        $_queries["province from"] = "select ID from udo_location where name='" . $_dataset["country from"]["value"] . "' and _type='udo_Country' and active=1;";
                                    }
                                    
                                    if ($_dataset["country to"]["value"] == "South Africa") {
                                        // If country is South Africa capture Province as parent
                                        $_queries["province to"] = "select ID from udo_location where name='" . $_dataset["province to"]["value"] . "' and _type='udo_Province' and active=1;";
                                    } else {
                                        // If country is not South Africa capture Province as country
                                        $_queries["province to"] = "select ID from udo_location where name='" . $_dataset["country to"]["value"] . "' and _type='udo_Country' and active=1;";
                                    }
                                    break;
                                }
                        }
                        
                        foreach ($_queries as $_sqlKey => $_sqlValue) {
                            $sqlResultA = $this->queryDB($_sqlValue);
                            if (count($sqlResultA) != 0) {
                                $_dataset[$_sqlKey]["id"] = intval($sqlResultA[0]["ID"]);
                                
                                // : If location does exist then check if location is linked to the customer and store the ID of the customer location link
                                if (strpos($_sqlKey, "location") !== FALSE) {
                                    $myQuery = preg_replace("/%n/", $_dataset[$_sqlKey]["value"], $this->_myqueries[0]);
                                    $myQuery = preg_replace("/%t/", $_dataset["customer"]["value"], $myQuery);
                                    $sqlResultB = $this->queryDB($myQuery);
                                    if (count($sqlResultB) != 0) {
                                        $_dataset[$_sqlKey]["link"] = intval($sqlResultB[0]["ID"]);
                                    } else {
                                        $_dataset[$_sqlKey]["link"] = NULL;
                                    }
                                }
                                // : End
                            } else {
                                $_dataset[$_sqlKey]["id"] = NULL;
                            }
                        }
                        // : End
                        
                        if ($_dataset["business unit"]["value"] != "Timber 24") {
                            // : Get IDS and fleet names for Zones
                            foreach ($_dataset as $_dataKey => $_dataValues) {
                                if (strpos($_dataKey, "zone") !== FALSE) {
                                    $myQuery = "select ID, fleet from udo_zone where name='" . $_dataset[$_dataKey]["value"] . "';";
                                    $sqlResult = $this->queryDB($myQuery);
                                    if (count($sqlResult) != 0) {
                                        $_dataset[$_dataKey]["id"] = intval($sqlResult[0]["ID"]);
                                        $_dataset[$_dataKey]["other"] = $sqlResult[0]["fleet"];
                                    } else {
                                        throw new Exception("Error: $_dataKey not found.");
                                    }
                                }
                            }
                        }
                        // : End
                        
                        // : Check if route and rate exists
                        
                        // Check and store route ID if exists
                        $_locationFrom_id = (string) "";
                        $_locationTo_id = (string) "";
                        
                        switch ($_dataset["business unit"]["value"]) {
                            case "Timber 24":
                                {
                                    $_locationFrom_id = $_dataset["location from point"]["id"];
                                    $_locationTo_id = $_dataset["location to point"]["id"];
                                    break;
                                }
                            default:
                                {
                                    $_locationFrom_id = $_dataset["location from town"]["id"];
                                    $_locationTo_id = $_dataset["location to town"]["id"];
                                    break;
                                }
                        }
                        
                        if (($_locationFrom_id != FALSE) && ($_locationTo_id != FALSE)) {
                            $myQuery = preg_replace("@%f@", $_locationFrom_id, AutomationLibrary::SQL_QUERY_ROUTE);
                            $myQuery = preg_replace("@%t@", $_locationTo_id, $myQuery);
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
                        
                        if ($_dataset["business unit"]["value"] != "Timber 24" && $this->_ratesonly == "false") {
                            
                            for ($i = 0; $i < 2; $i ++) {
                                // : Check locations exist and create them if they dont exist
                                $_locations = array();
                                foreach ($_dataset as $_dataKey => $_dataValues) {
                                    $_result = strpos($_dataKey, "location");
                                    if ($_result !== FALSE) {
                                        $_locations[$_dataKey] = $_dataValues;
                                    }
                                }
                                
                                $_type = NULL;
                                $_load = NULL;
                                
                                foreach ($_locations as $_locKey => $_aLocation) {
                                    try {
                                        $_currentLocation = $_locKey;
                                        $_process = "query location";
                                        $_locationTree = "";
                                        $_locationName = "";
                                        $_parentId = "";
                                        if (! $_aLocation["id"]) {
                                            
                                            // : Determine type of location
                                            if (((strpos($_locKey, "town")) != FALSE) && ((strpos($_locKey, "from")) != FALSE)) {
                                                $_load = "from";
                                                $_type = "udo_City";
                                                $_parentId = intval($_dataset["province from"]["id"]);
                                            } else 
                                                if (((strpos($_locKey, "town")) != FALSE) && ((strpos($_locKey, "to")) != FALSE)) {
                                                    $_load = "to";
                                                    $_type = "udo_City";
                                                    $_parentId = intval($_dataset["province to"]["id"]);
                                                } else 
                                                    if (((strpos($_locKey, "point")) != FALSE) && ((strpos($_locKey, "from")) != FALSE)) {
                                                        $_load = "from";
                                                        $_type = "udo_Point";
                                                        $_parentId = intval($_dataset["location from town"]["id"]);
                                                    } else 
                                                        if (((strpos($_locKey, "point")) != FALSE) && ((strpos($_locKey, "to")) != FALSE)) {
                                                            $_type = "udo_Point";
                                                            $_load = "to";
                                                            $_parentId = intval($_dataset["location to town"]["id"]);
                                                        }
                                            // : End
                                            // Build string for sql query like search for location
                                            // $_searchStr = preg_replace("@\s@", "%", $_aLocation["value"]) . "%"; - TEMPORARILY REMOVED - NEED BETTER LOGIC
                                            $_searchStr = $_aLocation["value"];
                                            $myQuery = "select ID, name from udo_location where name like '" . $_searchStr . "' and _type='" . $_type . "' and active=1;";
                                            $result = $this->queryDB($myQuery);
                                            
                                            if (count($result) != 0) {
                                                $_dataset[$_locKey]["id"] = intval($result[0]["ID"]);
                                                $_dataset[$_locKey]["value"] = $result[0]["name"];
                                                // Update last record
                                                $this->lastRecord = "Customer: " . $_dataset["customer"]["value"] . ", Route: " . $_dataset["location from town"]["value"] . " TO " . $_dataset["location to town"]["value"] . ", Rate Value: " . $_dataset["rate"]["value"] . ", Truck Description: " . $_dataset["truck type"]["value"];
                                            } else {
                                                
                                                echo "INFO: Build location tree - START" . PHP_EOL;
                                                // : Build location tree
                                                $_treeCount = 0;
                                                while ($_parentId != 0) {
                                                    $treeQuery = "select id, name, parent_id from udo_location where id=" . strval($_parentId) . ";";
                                                    
                                                    $result = $this->queryDB($treeQuery);
                                                    echo "DEBUG: Record been processed: NAME: " . $_aLocation['value'] . "; _TYPE: " . $_type . PHP_EOL;
                                                    
                                                    echo "DEBUG: SQL Query: " . $treeQuery . PHP_EOL;
                                                    echo "DEBUG: SQL Result:" . PHP_EOL;
                                                    var_dump($result);
                                                    if (count($result) != 0) {
                                                        $_parentId = intval($result[0]["parent_id"]);
                                                        $_locationName = $result[0]["name"];
                                                        switch ($_treeCount) {
                                                            case 0:
                                                                $_locationTree = $_locationName;
                                                                break;
                                                            default:
                                                                $_locationTree = $_locationName . " -- " . $_locationTree;
                                                                break;
                                                        }
                                                    } else {
                                                        throw new Exception("Cannot find parent for location type: $_type, name: " . $_aLocation["value"]);
                                                    }
                                                    $_treeCount ++;
                                                }
                                                echo "INFO: Build location tree - END" . PHP_EOL;
                                                
                                                // : End
                                                
                                                // If location not found then create new location
                                                switch ($_type) {
                                                    case "udo_City":
                                                        // : Create City
                                                        $_process = "create location - city";
                                                        $this->_session->open($this->_maxurl . self::CITY_URL);
                                                        
                                                        $e = $w->until(function ($session)
                                                        {
                                                            return $session->element("css selector", "div.toolbar-cell-create");
                                                        });
                                                        $this->_session->element("css selector", "div.toolbar-cell-create")->click();
                                                        
                                                        $e = $w->until(function ($session)
                                                        {
                                                            return $session->element("xpath", "//*[contains(text(),'Capture the details of City')]");
                                                        });
                                                        
                                                        $this->assertElementPresent("css selector", "#udo_City-14_0_0_name-14");
                                                        $this->assertElementPresent("css selector", "#udo_City-15__0_parent_id-15");
                                                        $this->assertElementPresent("css selector", "#checkbox_udo_City-2_0_0_active-2");
                                                        $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                                        
                                                        $this->_session->element("css selector", "#udo_City-14_0_0_name-14")->sendKeys($_aLocation["value"]);
                                                        $this->_session->element("xpath", "//*[@id='udo_City-15__0_parent_id-15']/option[text()='$_locationTree']")->click();
                                                        $this->_session->element("css selector", "#checkbox_udo_City-2_0_0_active-2")->click();
                                                        $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                                                        
                                                        $e = $w->until(function ($session)
                                                        {
                                                            return $session->element("css selector", "div.toolbar-cell-create");
                                                        });
                                                        
                                                        $this->_session->element("css selector", "div.toolbar-cell-create")->click();
                                                        
                                                        $e = $w->until(function ($session)
                                                        {
                                                            return $session->element("xpath", "//*[contains(text(),'Create Zones - City')]");
                                                        });
                                                        
                                                        $this->assertElementPresent("css selector", "#udo_ZoneCity_link-5__0_zone_id-5");
                                                        $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                                        
                                                        $this->_session->element("xpath", "//*[@id='udo_ZoneCity_link-5__0_zone_id-5']/option[text()='" . $_dataset["zone " . $_load]["value"] . " " . $_dataset["zone " . $_load]["other"] . "']")->click();
                                                        $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                                                        
                                                        $e = $w->until(function ($session)
                                                        {
                                                            return $session->element("css selector", "div.toolbar-cell-create");
                                                        });
                                                        
                                                        $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                                        $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                                                        break;
                                                    // : End Case
                                                    case "udo_Point":
                                                    case "default":
                                                        // : Create Point
                                                        $_process = "create location - point";
                                                        $this->_session->open($this->_maxurl . self::POINT_URL);
                                                        
                                                        $e = $w->until(function ($session)
                                                        {
                                                            return $session->element("css selector", "div.toolbar-cell-create");
                                                        });
                                                        $this->_session->element("css selector", "div.toolbar-cell-create")->click();
                                                        
                                                        $e = $w->until(function ($session)
                                                        {
                                                            return $session->element("xpath", "//*[contains(text(),'Capture the details of Point')]");
                                                        });
                                                        
                                                        $this->assertElementPresent("css selector", "#udo_Point-14_0_0_name-14");
                                                        $this->assertElementPresent("css selector", "#udo_Point-15__0_parent_id-15");
                                                        $this->assertElementPresent("css selector", "#checkbox_udo_Point-2_0_0_active-2");
                                                        $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                                        $this->_session->element("css selector", "#udo_Point-14_0_0_name-14")->sendKeys($_aLocation["value"]);
                                                        
                                                        $this->_session->element("xpath", "//*[@id='udo_Point-15__0_parent_id-15']/option[text()='$_locationTree']")->click();
                                                        $this->_session->element("css selector", "#checkbox_udo_Point-2_0_0_active-2")->click();
                                                        $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                                                        
                                                        $e = $w->until(function ($session)
                                                        {
                                                            return $session->element("css selector", "div.toolbar-cell-create");
                                                        });
                                                        break;
                                                    // : End Case
                                                }
                                            }
                                        }
                                    } catch (Exception $e) {
                                        $_autoLib->addErrorRecord($this->_error, $this->_scrDir, $e->getMessage(), $this->lastRecord . ". Object data that failed: " . $_currentLocation, $_process);
                                    }
                                }
                            }
                        }
                        // : End
                        
                        // : Check locations are linked to customer
                        
                        if ($_dataset["business unit"]["value"] != "Timber 24" && $this->_ratesonly == "false") {
                            foreach ($_dataset as $_dataKey => $_dataValues) {
                                
                                try {
                                    
                                    if (((! $_dataValues["id"]) && (! $_dataValues["link"])) || (($_dataValues["id"]) && (! $_dataValues["link"]))) {
                                        if ((strpos($_dataKey, "location") !== FALSE) && (strpos($_dataKey, "point") !== FALSE)) {
                                            $_process = "create customer location";
                                            $_currentLocation = $_dataValues["value"];
                                            if (! $_dataValues["id"]) {
                                                $myQuery = preg_replace("/%s/", $_dataValues["value"], $this->_myqueries[5]);
                                                $myQuery = preg_replace("/%t/", "udo_Point", $myQuery);
                                                $sqlResult = $this->queryDB($myQuery);
                                                if (count($sqlResult) != 0) {
                                                    $_dataset[$_dataKey]["id"] = $sqlResult[0]["ID"];
                                                } else {
                                                    throw new Exception("Location not found after location process has been run. Please investigate. " . $_dataValues["value"]);
                                                }
                                            }
                                            // : If location does exist and link does not exist then create customer location link
                                            if ((($_dataValues["id"]) != FALSE) && (! $_dataValues["link"])) {
                                                // Load URL for MAX customers page
                                                $this->_session->open($this->_maxurl . self::CUSTOMER_URL . $_dataset["customer"]["id"]);
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
                                                    $this->_session->open($this->_maxurl . self::LOCATION_BU_URL . $_dataset[$_dataKey]["link"]);
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
                                            }
                                            // : End
                                        }
                                    }
                                } catch (Exception $e) {
                                    $_autoLib->addErrorRecord($this->_error, $this->_scrDir, $e->getMessage(), $this->lastRecord . ". Object data that failed: " . $_currentLocation, $_process);
                                }
                            }
                        }
                        
                        // : End
                        
                        // : Check offloading customer exists
                        if ($_dataset["business unit"]["value"] != "Timber 24" && $this->_ratesonly == "false") {
                            
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
                                    $this->_session->open($this->_maxurl . self::CUSTOMER_URL . $_dataset["customer"]["id"]);
                                    
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
                                        $this->_session->open($this->_maxurl . self::OFF_CUST_BU_URL . $_dataset["offloading customer"]["link"]);
                                        
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
                                $_autoLib->addErrorRecord($this->_error, $this->_scrDir, $e->getMessage(), $this->lastRecord . ". Object data that failed: " . $_currentLocation, $_process);
                            }
                        }
                        // : End
                        
                        // : Check if route and rate exists for customer and create route and rate if they dont exist
                        
                        try {
                            
                            if ((! $_dataset["rate"]["id"])) {
                                
                                // : If route does not exist from previous check, check again and store route ID if it exists
                                if (($_locationFrom_id != FALSE) && ($_locationTo_id != FALSE) && (! $_dataset["rate"]["other"])) {
                                    
                                    $_process = "check if route exists";
                                    
                                    $myQuery = preg_replace("@%f@", $_locationFrom_id, AutomationLibrary::SQL_QUERY_ROUTE);
                                    $myQuery = preg_replace("@%t@", $_locationTo_id, $myQuery);
                                    $sqlResult = $this->queryDB($myQuery);
                                    
                                    if (count($sqlResult) != 0) {
                                        $_dataset["rate"]["other"] = $sqlResult[0]["ID"];
                                    } else {
                                        $_dataset["rate"]["other"] = NULL;
                                    }
                                }
                                                                
                                $_process = "begin create rate process";
                                // Get all currently open windows
                                $_winAll = $this->_session->window_handles();
                                // Set window focus to main window
                                $this->_session->focusWindow($_winAll[0]);
                                // If there is more than 1 window open then close all but main window
                                if (count($_winAll) > 1) {
                                    $this->clearWindows();
                                }
                                
                                $_autoLib->CONSOLE_OUTPUT("BU Value", "Output the value of [business unit][value]", "sql", "na", $_dataset["business unit"]["value"]);
                                // : Create route if it does not exist
                                if (! $_dataset["rate"]["other"]) {
                                    try {
                                        $_process = "create route";
                                        $_locationFrom_value = (string)"";
                                        $_locationTo_value = (string)"";
                                        
                                        switch ($_dataset["business unit"]["value"]) {
                                            case "Timber 24":
                                                {
                                                    $_locationFrom_value = $_dataset["location from point"]["value"];
                                                    $_locationTo_value = $_dataset["location to point"]["value"];
                                                    break;
                                                }
                                            default:
                                                {
                                                    $_locationFrom_value = $_dataset["location from town"]["value"];
                                                    $_locationTo_value = $_dataset["location to town"]["value"];
                                                    break;
                                                }
                                        }        
                                        
                                        $_maxRouteRate->maxRouteCreate($_dataset["business unit"]["value"], $_locationFrom_value, $_locationTo_value, $_dataset["expected kms"]["value"]);
                                        
                                        // : If route does not exist from previous check, check again and store route ID if it exists
                                        if (($_locationFrom_id != FALSE) && ($_locationTo_id != FALSE) && (! $_dataset["rate"]["other"])) {
                                            
                                            $myQuery = preg_replace("@%f@", $_locationFrom_id, AutomationLibrary::SQL_QUERY_ROUTE);
                                            $myQuery = preg_replace("@%t@", $_locationTo_id, $myQuery);
                                            $sqlResult = $this->queryDB($myQuery);
                                            
                                            if (count($sqlResult) != 0) {
                                                $_dataset["rate"]["other"] = $sqlResult[0]["ID"];
                                            } else {
                                                $_dataset["rate"]["other"] = NULL;
                                            }
                                        }
                                    } catch (Exception $e) {}
                                }
                                // : End
                                
                                // Load the MAX customer page
                                $this->_session->open($this->_maxurl . self::CUSTOMER_URL . $_dataset["customer"]["id"]);
                                
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
                                
                                // Check and store rate ID if exists
                                echo "INFO: Looking for rate ID." . PHP_EOL;
                                $myQuery = preg_replace("@%ro@", $_dataset["rate"]["other"], $this->_myqueries[8]);
                                $myQuery = preg_replace("@%ra@", $_dataset["rate type"]["id"], $myQuery);
                                $myQuery = preg_replace("@%g@", $objectregistry_id, $myQuery);
                                $myQuery = preg_replace("@%c@", $_dataset["customer"]["id"], $myQuery);
                                $myQuery = preg_replace("@%d@", $_dataset["truck type"]["id"], $myQuery);
                                $myQuery = preg_replace("@%m@", $_dataset["contribution model"]["value"], $myQuery);
                                $myQuery = preg_replace("@%b@", $_dataset["business unit"]["id"], $myQuery);
                                $sqlResult = $this->queryDB($myQuery);
                                echo "DEBUG: SQL Query: " . $myQuery . PHP_EOL;
                                echo "DEBUG: SQL Results: " . PHP_EOL;
                                var_dump($sqlResult);
                                
                                if (count($sqlResult) != 0) {
                                    $_dataset["rate"]["id"] = $sqlResult[0]["ID"];
                                } else {
                                    $_dataset["rate"]["id"] = NULL;
                                }
                                // : End
                                echo "DEBUG: RATE ID VALUE: " . $_dataset["rate"]["id"] . PHP_EOL;
                                
                                if ($_dataset["rate"]["id"] == FALSE) {
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
                                    
                                    // If contribution model is not null then select its value
                                    if ($_dataset['contribution model']['value']) {
                                        $this->_session->element("xpath", "//*[@id='udo_Rates-20__0_model-20']/option[text()='" . $_dataset["contribution model"]["value"] . "']")->click();
                                    }
                                    
                                    $this->_session->element("xpath", "//*[@id='checkbox_udo_Rates-15_0_0_enabled-15']")->click();
                                    $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                                } else {
                                    $this->_session->element("css selector", "input[type=submit][name=abort]")->click();
                                }
                                
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
                            if (($_locationFrom_id != FALSE) && ($_locationTo_id != FALSE)) {
                                $myQuery = preg_replace("@%f@", $_locationFrom_id, AutomationLibrary::SQL_QUERY_ROUTE);
                                $myQuery = preg_replace("@%t@", $_locationTo_id, $myQuery);
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
                                try {
                                    $_process = "create date range values for route and rate";
                                    if (($_dataset["rate"]["id"]) && ($_dataset["rate"]["other"]) && ($_drvValue)) {
                                        // Prepare url string to load next
                                        $rateurl = preg_replace("/%s/", $_dataset["rate"]["id"], $this->_maxurl . self::RATEVAL_URL);
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
                                        $this->_session->element("xpath", "//*[@id='DateRangeValue-2_0_0_beginDate-2']")->sendKeys($_dataset['start date']['value']);
                                        
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
                                        $_result = "";
                                        if (count($sqlResult)) {
                                            foreach ($sqlResult as $resultArray) {
                                                $_result .= implode(",", $resultArray);
                                            }
                                        }
                                        $_errorData = "Date Range Values: " . PHP_EOL . implode(",", $_dateRangeValues) . PHP_EOL . "SQL Query Dump: " . PHP_EOL . $myQuery . PHP_EOL . "SQL Result Dump:" . PHP_EOL . $_result;
                                        $_errmsg = preg_replace("/%s/", $_drvKey, AutomationLibrary::ERR_NO_DATE_RANGE_VALUE);
                                        $_autoLib->addErrorRecord($this->_error, $this->_scrDir, $_errmsg, $this->lastRecord . PHP_EOL . $_errorData, $_process);
                                    }
                                } catch (Exception $e) {}
                            }
                            // : End
                        } catch (Exception $e) {
                            $_autoLib->addErrorRecord($this->_error, $this->_scrDir, $e->getMessage(), $this->lastRecord, $_process);
                        }
                        
                        // : End
                        
                        // : End
                    } catch (Exception $e) {
                        $_autoLib->addErrorRecord($this->_error, $this->_scrDir, $e->getMessage(), $this->lastRecord, "Route and Rate");
                    }
                }
                // : End
            } else {
                // Add code here
            }
            
            $_maxLoginLogout->maxLogout($this->_version);
            
            // : Close connection to database
            $db = null;
            if ($_dbStatus) {
                $this->closeDB();
            }
            // : End
            
            // Terminate session
            $this->_session->close();
            // : End
            
            // : End
            
            // : If errors occured. Create xls of entries that failed.
            if (count($this->_error) != 0) {
                $_xlsfilename = (dirname(__FILE__) . $this->_errDir . self::DS . date("Y-m-d_His_") . basename(__FILE__, ".php") . ".xlsx");
                $_autoLib->writeExcelFile($_xlsfilename, $this->_error, $_xlsColumns, basename(__FILE__, ".php"), "error_report", "error_report");
                if (file_exists($_xlsfilename)) {
                    print("Excel error report written successfully to file: $_xlsfilename");
                } else {
                    print("Excel error report write unsuccessful");
                }
            }
            echo "Dumping the array of errors:" . PHP_EOL;
            var_dump($this->_error);
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
     * MAXLive_Rates_Create::openDB($dsn, $username, $password, $options)
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
     * MAXLive_Rates_Create::assertElementPresent($_using, $_value)
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
     * MAXLive_Rates_Create::assertElementPresent($_title)
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
     * MAXLive_Rates_Create::closeDB()
     * Close connection to Database
     */
    private function closeDB()
    {
        $this->_db = null;
    }

    /**
     * MAXLive_Rates_Create::queryDB($sqlquery)
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
     * MAXLive_Rates_Create::clearWindows()
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
     * MAXLive_Rates_Create::importCSVFileIntoArray($csvFile)
     * From supplied csv file save data into multidimensional array
     *
     * @param string: $csvFile            
     * @param array: $_result            
     */
    private function importCSVFileIntoArray($csvFile)
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
