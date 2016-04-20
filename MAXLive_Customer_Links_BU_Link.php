<?php
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
include_once 'MAX_LoginLogout.php';
include_once 'AutomationLibrary.php';

/**
 * MAXLive_Customer_Links_BU_Link.php
 *
 * @package MAXLive_Customer_Links_BU_Link
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
class MAXLive_Customer_Links_BU_Link extends PHPUnit_Framework_TestCase
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

    const XLS_CREATOR = "MAXLive_Customer_Links_BU_Link.php";

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

    protected $_offloadcustomer;

    protected $_locations;

    protected $_maxurl;

    protected $_tmp;

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
     * MAXLive_Customer_Links_BU_Link::getFunctionErrorMsg()
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
     * MAXLive_Customer_Links_BU_Link::stringHypenFix($_value)
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
     * MAXLive_Customer_Links_BU_Link::__construct()
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
        if ((array_key_exists("offloadcustomer", $data) && $data["offloadcustomer"]) && (array_key_exists("locations", $data) && $data["locations"]) && (array_key_exists("version", $data) && $data["version"]) && (array_key_exists("browser", $data) && $data["browser"]) && (array_key_exists("wdport", $data) && $data["wdport"]) && (array_key_exists("csv", $data) && $data["csv"]) && (array_key_exists("errordir", $data) && $data["errordir"]) && (array_key_exists("screenshotdir", $data) && $data["screenshotdir"]) && (array_key_exists("datadir", $data) && $data["datadir"]) && (array_key_exists("ip", $data) && $data["ip"]) && (array_key_exists("username", $data) && $data["username"]) && (array_key_exists("password", $data) && $data["password"]) && (array_key_exists("welcome", $data) && $data["welcome"]) && (array_key_exists("mode", $data) && $data["mode"])) {
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
            $this->_offloadcustomer = strtolower($data["offloadcustomer"]);
            $this->_locations = strtolower($data["locations"]);
            
            // Determine MAX URL to be used for this test run
            $this->_maxurl = AutomationLibrary::getMAXURL($this->_mode, $this->_version);
        } else {
            echo "The correct data is not present in " . self::INI_FILE . ". Please confirm. Fields are username, password, welcome and mode" . PHP_EOL;
            return FALSE;
        }
    }

    /**
     * MAXLive_Customer_Links_BU_Link::__destruct()
     * Class destructor
     * Allow for garbage collection
     */
    public function __destruct()
    {
        unset($this);
    }
    // : End
    
    /**
     * MAXLive_Customer_Links_BU_Link::setUp()
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
     * MAXLive_Customer_Links_BU_Link::testCreateContracts()
     * Pull F and V Contract data and automate creation of F and V Contracts
     */
    public function testCreateRates()
    {
        try {
            // : Define local variables
            $_dbStatus = FALSE;
            $_match = 0;
            $_process = (string) "";
            $_objectregistry_id = (int) 0;
            // Define array of keywords to validate data headers
            $_headers = (array) array(
                "id",
                "bu"
            );
            
            // : Error report columns for the spreadsheet data
            $_xlsColumns = array(
                "Error_Msg",
                "Record Detail",
                "Type"
            );
            
            // : End
            
            // Construct full path and filename for csv file using script home dir and data dir path to file
            $_file = realpath($this->_dataDir) . self::DS . $this->_csv;
            
            // Import data from file and if successful continue script else terminate script and return error
            if (($_data = $this->importCSVFileIntoArray($_file)) !== FALSE) {
                
                // : Create a persistant connection to the database
                $_mysqlDsn = preg_replace("/%s/", $this->_ip, $this->_dbdsn);
                if ($this->openDB($_mysqlDsn, $this->_dbuser, $this->_dbpwd, $this->_dboptions) !== FALSE) {
                    $_dbStatus = TRUE;
                } else {
                    throw new Exception(self::COULD_NOT_CONNECT_MYSQL);
                }
                // : End
                
                $session = $this->_session;
                $this->_session->setPageLoadTimeout(60);
                $w = new PHPWebDriver_WebDriverWait($session, 30);
                
                $_autoLib = new AutomationLibrary($this->_session, $this, $w, $this->_mode, $this->_version);
                $_maxLoginLogout = new maxLoginLogout($_autoLib, $this->_maxurl);
                
                // Log into MAX
                if (! $_maxLoginLogout->maxLogin($this->_username, $this->_password, $this->_welcome)) {
                    throw new Exception($_maxLoginLogout->getLastError());
                }
                
                // : Set variables to be used on page depending on type of links been added. Offloading or Customer Location BU links
                $_element_template_var = (array) array(
                    "element_selector" => "",
                    "element_value" => ""
                );
                $_record_page_create_button = (array) $_element_template_var;
                $_create_bu_link_page_heading_label = (array) $_element_template_var;
                $_create_bu_link_page_select_box = (array) $_element_template_var;
                $_create_bu_link_page_save_btn = (array) $_element_template_var;
                $_locations = (bool) false;
                $_offloadcustomer = (bool) false;
                $_url = "";
                
                // : End
                if (($this->_locations == "true") && ($this->_offloadcustomer == "false")) {
                    // : Link Customer Location Business Unit Links
                    $_url = AutomationLibrary::URL_CUST_LOCATION_BU;
                    
                    $_locations = (bool) true;
                    $_offloadcustomer = (bool) false;
                    
                    $_record_page_create_button["element_selector"] = "xpath";
                    $_record_page_create_button["element_value"] = "//div[@id='button-create']";
                    
                    $_create_bu_link_page_heading_label["element_selector"] = "xpath";
                    $_create_bu_link_page_heading_label["element_value"] = "//*[contains(text(),'Create Customer Locations - Business Unit')]";
                    
                    $_create_bu_link_page_select_box["element_selector"] = "xpath";
                    $_create_bu_link_page_select_box["element_value"] = "//*[@id='udo_CustomerLocationsBusinessUnit_link-2__0_businessUnit_id-2']";
                    
                    $_create_bu_link_page_save_btn["element_selector"] = "css selector";
                    $_create_bu_link_page_save_btn["element_value"] = "input[name=save][type=submit]";
                    // : End
                } else 
                    if (($this->_offloadcustomer == "true") && ($this->_locations == "false")) {
                        // : Link Customer Offloading Customer Business Unit Links
                        $_url = AutomationLibrary::URL_OFFLOAD_CUST_BU;
                        
                        $_locations = (bool) false;
                        $_offloadcustomer = (bool) true;
                        
                        $_record_page_create_button["element_selector"] = "xpath";
                        $_record_page_create_button["element_value"] = "//div[@id='button-create']";
                        
                        $_create_bu_link_page_heading_label["element_selector"] = "xpath";
                        $_create_bu_link_page_heading_label["element_value"] = "//*[contains(text(),'Create Offloading Customers - Business Unit')]";
                        
                        $_create_bu_link_page_select_box["element_selector"] = "xpath";
                        $_create_bu_link_page_select_box["element_value"] = "//*[@id='udo_OffloadingCustomersBusinessUnit_link-2__0_businessUnit_id-2']";
                        
                        $_create_bu_link_page_save_btn["element_selector"] = "css selector";
                        $_create_bu_link_page_save_btn["element_value"] = "input[name=save][type=submit]";
                        // : End
                    }
                
                if (($_locations && ! $_offloadcustomer) || (! $_locations && $_offloadcustomer)) {
                    foreach ($_data as $_key => $_value) {
                        try {
                            if ($_locations) {
                                $_urlstr = $_url . $_value["id"];
                            } else 
                                if ($_offloadcustomer) {
                                    $_urlstr = preg_replace("/%d/", $_value["id"], $_url);
                                }
                            if ($_urlstr) {
                                $this->_session->open($this->_maxurl . $_urlstr);
                                
                                // Save values into class property tmp to be able to access information within the loop below
                                $this->_tmp['element_selector'] = $_record_page_create_button["element_selector"];
                                $this->_tmp['element_value'] = $_record_page_create_button["element_value"];
                                
                                $e = $w->until(function ($session) {
                                    return $session->element($this->_tmp['element_selector'], $this->_tmp['element_value']);
                                });
                                
                                $this->_session->element($_record_page_create_button["element_selector"], $_record_page_create_button["element_value"])->click();
                                
                                // Save values into class property tmp to be able to access information within the loop below
                                $this->_tmp['element_selector'] = $_create_bu_link_page_heading_label["element_selector"];
                                $this->_tmp['element_value'] = $_create_bu_link_page_heading_label["element_value"];
                                
                                $e = $w->until(function ($session) {
                                    return $session->element($this->_tmp['element_selector'], $this->_tmp['element_value']);
                                });
                                
                                $_autoLib->assertElementPresent($_create_bu_link_page_select_box["element_selector"], $_create_bu_link_page_select_box["element_value"]);
                                $_autoLib->assertElementPresent($_create_bu_link_page_save_btn["element_selector"], $_create_bu_link_page_save_btn["element_value"]);
                                
                                $this->_session->element($_create_bu_link_page_select_box["element_selector"], $_create_bu_link_page_select_box["element_value"] . "/option[text()='" . $_value['bu'] . "']")->click();
                                $this->_session->element($_create_bu_link_page_save_btn["element_selector"], $_create_bu_link_page_save_btn["element_value"])->click();
                                
                                // Save values into class property tmp to be able to access information within the loop below
                                $this->_tmp['element_selector'] = $_record_page_create_button["element_selector"];
                                $this->_tmp['element_value'] = $_record_page_create_button["element_value"];
                                
                                $e = $w->until(function ($session) {
                                    return $session->element($this->_tmp['element_selector'], $this->_tmp['element_value']);
                                });
                            } else {
                                throw new Exception("Error: location and offloadcustomer boolean variables are both false. Could not build url string.");
                            }
                        } catch (Exception $e) {
                            $_autoLib->addErrorRecord($this->_error, $this->_scrDir, $e->getMessage(), $_value["id"], "Link BU to Customer Location");
                        }
                    }
                } else {
                    throw new Exception("locations and offloadcustomer cannot both be true. Please set only one of these values to true.");
                }
            } else {
                throw new Exception("No data supplied in the specified file: $_file");
            }
        } catch (Exception $e) {
            $_autoLib->addErrorRecord($this->_error, $this->_scrDir, $e->getMessage(), "NA", "Determine if location and offloadcustomer are both true.");
        }
        // : Tear down
        $_maxLoginLogout->maxLogout();
        
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
            $_xlsfilename = realpath($this->_errDir) . self::DS . date("Y-m-d_His_") . basename(__FILE__, ".php") . ".xlsx";
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
    }
    
    // : Private Functions
    
    /**
     * MAXLive_Customer_Links_BU_Link::openDB($dsn, $username, $password, $options)
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
     * MAXLive_Customer_Links_BU_Link::assertElementPresent($_title)
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
     * MAXLive_Customer_Links_BU_Link::closeDB()
     * Close connection to Database
     */
    private function closeDB()
    {
        $this->_db = null;
    }

    /**
     * MAXLive_Customer_Links_BU_Link::queryDB($sqlquery)
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
     * MAXLive_Customer_Links_BU_Link::clearWindows()
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
     * MAXLive_Customer_Links_BU_Link::importCSVFileIntoArray($csvFile)
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
