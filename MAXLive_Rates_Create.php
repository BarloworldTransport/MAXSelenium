<?php
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
include_once 'PHPUnit/Extensions/PHPExcel/Classes/PHPExcel.php';

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
class MAXLive_Rates_Create extends PHPUnit_Framework_TestCase {
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
	protected $_dataDir;
	protected $_errDir;
	protected $_scrDir;
	protected $_wdport;
	protected $_browser;
	protected $_ip;
	protected $_proxyip;
	protected $_csv;
	protected $_maxurl;
	protected $_error = array ();
	protected $_functionError = "";
	protected $_db;
	protected $_dbdsn = "mysql:host=%s;dbname=max2;charset=utf8;";
	protected $_dbuser = "root";
	protected $_dbpwd = "kaluma";
	protected $_dboptions = array (
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true 
	);
	protected $_myqueries = array (
			"select ID from udo_customerlocations where location_id IN (select ID from udo_location where name='%n' and _type='udo_Point') and customer_id IN (select ID from udo_customer where tradingName='%t');",
			"select ID from udo_offloadingcustomers where offloadingCustomer_id IN (select ID from udo_customer where tradingName='%o') and customer_id IN (select ID from udo_customer where tradingName='%t');",
			"select ID from udo_customer where tradingName='%t';",
			"select ID from udo_rates where route_id IN (select ID from udo_route where locationFrom_id IN (select ID from udo_location where name='%f') and locationTo_id IN (select ID from udo_location where name='%t')) and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and model='%m' and businessUnit_id=%b and rateType_id=%r;",
			"select ID from objectregistry where handle = 'udo_Customer';",
			"select ID from udo_location where name='%s' and _type='%t';",
			"select ID from udo_zone where name='%s';" 
	);
	
	// : Public functions
	// : Getters
	
	/**
	 * MAXLive_Rates_Create::getFunctionErrorMsg()
	 * Access protected variable used to store global error message
	 *
	 * @param string: $this->_functionError        	
	 */
	public function getFunctionErrorMsg() {
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
	public function stringHypenFix($_value) {
		$_result = preg_replace ( "/–/", "-", $_value );
		return $_result;
	}
	
	// : Magic
	/**
	 * MAXLive_Rates_Create::__construct()
	 * Class constructor
	 */
	public function __construct() {
		$ini = dirname ( realpath ( __FILE__ ) ) . self::DS . self::INI_DIR . self::DS . self::INI_FILE;
		if (is_file ( $ini ) === FALSE) {
			echo "No " . self::INI_FILE . " file found. Please refer to documentation for script to determine which fields are required and their corresponding values." . PHP_EOL;
			return FALSE;
		}
		$data = parse_ini_file ( $ini );
		if ((array_key_exists ( "browser", $data ) && $data ["browser"]) && (array_key_exists ( "wdport", $data ) && $data ["wdport"]) && (array_key_exists ( "csv", $data ) && $data ["csv"]) && (array_key_exists ( "errordir", $data ) && $data ["errordir"]) && (array_key_exists ( "screenshotdir", $data ) && $data ["screenshotdir"]) && (array_key_exists ( "datadir", $data ) && $data ["datadir"]) && (array_key_exists ( "ip", $data ) && $data ["ip"]) && (array_key_exists ( "username", $data ) && $data ["username"]) && (array_key_exists ( "password", $data ) && $data ["password"]) && (array_key_exists ( "welcome", $data ) && $data ["welcome"]) && (array_key_exists ( "mode", $data ) && $data ["mode"])) {
			$this->_username = $data ["username"];
			$this->_password = $data ["password"];
			$this->_welcome = $data ["welcome"];
			$this->_dataDir = $data ["datadir"];
			$this->_errDir = $data ["errordir"];
			$this->_scrDir = $data ["screenshotdir"];
			$this->_mode = $data ["mode"];
			$this->_ip = $data ["ip"];
			$this->_wdport = $data ["wdport"];
			$this->_proxyip = $data ["proxy"];
			$this->_browser = $data ["browser"];
			$this->_csv = $data ["csv"];
			switch ($this->_mode) {
				case "live" :
					$this->_maxurl = self::LIVE_URL;
					break;
				default :
					$this->_maxurl = self::TEST_URL;
			}
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
	public function __destruct() {
		unset ( $this );
	}
	// : End
	
	/**
	 * MAXLive_Rates_Create::setUp()
	 * Create new class object and initialize session for webdriver
	 */
	public function setUp() {
		$wd_host = "http://localhost:$this->_wdport/wd/hub";
		self::$driver = new PHPWebDriver_WebDriver ( $wd_host );
		$desired_capabilities = array ();
		$proxy = new PHPWebDriver_WebDriverProxy ();
		$proxy->httpProxy = $this->_proxyip;
		$proxy->add_to_capabilities ( $desired_capabilities );
		$this->_session = self::$driver->session ( $this->_browser, $desired_capabilities );
	}
	
	/**
	 * MAXLive_Rates_Create::testCreateContracts()
	 * Pull F and V Contract data and automate creation of F and V Contracts
	 */
	public function testCreateRates() {
		
		// : Define local variables
		$_dbStatus = FALSE;
		$_match = 0;
		$_objectregistry_id = ( int ) 0;
		// Define array of keywords to validate data headers
		$_headers = ( array ) array (
				"customer",
				"offloading customer",
				"province from",
				"location from town",
				"location from point",
				"province to",
				"location to town",
				"location to point",
				"rate type",
				"rate",
				"expected kms",
				"expected empty kms",
				"days per trip",
				"truck type",
				"lead kms",
				"minimum tons",
				"contribution model",
				"business unit",
				"zone from",
				"zone to" 
		);
		
		$_keys = array (
				"id" => 0,
				"value" => "",
				"link" => 0,
				"bulink" => 0 
		);
		
		$_dataset = array_fill_keys ( $_headers, $_keys );
		$_colcount = ( int ) count ( $_headers );
		$_columns = ( array ) array ();
		// : End
		
		// Construct full path and filename for csv file using script home dir and data dir path to file
		$_file = dirname ( __FILE__ ) . $this->_dataDir . self::DS . $this->_csv;
		
		/*
		 * Will be used at a later stage to make validation more dynamic $_headers = ( array ) array ( 0 => array("customer", "!offload"), 1 => array("offload", "customer"), 2 => array("location", "from", "town"), 3 => array("location", "from", "point"), 4 => array("location","to","town"), 5 => array("location","to","point"), 6 => array("rate","type"), 7 => array("rate"), 8 => array("expected","kms"), 9 => array("expected","empty","kms"), 10 => array("days","per","trip"), 11 => array("truck","type"), 12 => array("lead", "kms"), 13 => array("minimum","tons"), 14 => array("contrib","model"), 15 => array("business","unit") );
		 */
		
		// Import data from file and if successful continue script else terminate script and return error
		if (($_data = $this->ImportCSVFileIntoArray ( $_file )) !== FALSE) {
			
			// : Get column headers from imported data and validate that headers are all present
			$_columns = array_keys ( $_data [1], $_headers );
			foreach ( $_data [0] as $_key => $_value ) {
				$_columns [] = $_key;
				$_searchKey = "/^" . strtolower ( preg_replace ( "@\s@", "\s", $_key ) . "$/" );
				$_result = preg_grep ( $_searchKey, $_headers );
				if (count ( $_result ) != 0) {
					$_match ++;
				}
			}
			;
			// : End
			
			if ($_match === $_colcount) {
				
				// : Create a persistant connection to the database
				$_mysqlDsn = preg_replace ( "/%s/", $this->_ip, $this->_dbdsn );
				if ($this->openDB ( $_mysqlDsn, $this->_dbuser, $this->_dbpwd, $this->_dboptions ) !== FALSE) {
					$_dbStatus = TRUE;
				} else {
					throw new Exception ( self::COULD_NOT_CONNECT_MYSQL );
				}
				// : End
				
				// : Get data from database to setup some variables before starting
				
				// Get objectregistry_id for udo_Customer
				$myQuery = $this->_myqueries [4];
				$result = $this->queryDB ( $myQuery );
				if (count ( $result ) != 0) {
					$objectregistry_id = $result [0] ["ID"];
				} else {
					throw new Exception ( "Error: Object registry record for udo_customer not found." );
				}
				
				// : End
				
				// : Initiate Session
				$session = $this->_session;
				$this->_session->setPageLoadTimeout ( 90 );
				// Create a reference to the session object for use with waiting for elements to be present
				$w = new PHPWebDriver_WebDriverWait ( $this->_session );
				// : End
				
				// : Login
				try {
					// Load MAX home page
					$this->_session->open ( $this->_maxurl );
					// : Wait for page to load and for elements to be present on page
					if ($this->_mode == "live") {
						$e = $w->until ( function ($session) {
							return $session->element ( 'css selector', "#contentFrame" );
						} );
						$iframe = $this->_session->element ( 'css selector', '#contentFrame' );
						$this->_session->switch_to_frame ( $iframe );
					}
					$e = $w->until ( function ($session) {
						return $session->element ( 'css selector', 'input[id=identification]' );
					} );
					// : End
					$this->assertElementPresent ( 'css selector', 'input[id=identification]' );
					$this->assertElementPresent ( 'css selector', 'input[id=password]' );
					$this->assertElementPresent ( 'css selector', 'input[name=submit][type=submit]' );
					$e->sendKeys ( $this->_username );
					$e = $this->_session->element ( 'css selector', 'input[id=password]' );
					$e->sendKeys ( $this->_password );
					$e = $this->_session->element ( 'css selector', 'input[name=submit][type=submit]' );
					$e->click ();
					// Switch out of frame
					if ($this->_mode == "live") {
						$this->_session->switch_to_frame ();
					}
					
					// : Wait for page to load and for elements to be present on page
					if ($this->_mode == "live") {
						$e = $w->until ( function ($session) {
							return $session->element ( 'css selector', "#contentFrame" );
						} );
						$iframe = $this->_session->element ( 'css selector', '#contentFrame' );
						$this->_session->switch_to_frame ( $iframe );
					}
					$e = $w->until ( function ($session) {
						return $session->element ( "xpath", "//*[text()='" . $this->_welcome . "']" );
					} );
					$this->assertElementPresent ( "xpath", "//*[text()='" . $this->_welcome . "']" );
					// Switch out of frame
					if ($this->_mode == "live") {
						$this->_session->switch_to_frame ();
					}
				} catch ( Exception $e ) {
					throw new Exception ( "Error: Failed to log into MAX." . PHP_EOL . $e->getMessage () );
				}
				// : End
				
				// : Load Planningboard to rid of iframe loading on every page from here on
				$this->_session->open ( $this->_maxurl . self::PB_URL );
				$e = $w->until ( function ($session) {
					return $session->element ( "xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]" );
				} );
				// : End
				// : Run line by line through each data entry which === 1 csv row
				foreach ( $_data as $_value ) {
					
					try {
						// : Prepare variables
						foreach ( $_dataset as $_aKey => $_aValue ) {
							$_dataset [$_aKey] ["value"] = $_value [$_aKey];
						}
						// : End
						
						// Get truck description ID
						$myQuery = "select ID from udo_truckdescription where description='" . $_dataset ["truck type"] ["value"] . "';";
						$result = $this->queryDB ( $myQuery );
						if (count ( $result ) != 0) {
							$_dataset ["truck type"] ["id"] = intval ( $result [0] ["ID"] );
						} else {
							throw new Exception ( "Error: Truck description not found. Please check and amend truck description." );
						}
						
						// Get customer ID
						$myQuery = "select ID from udo_customer where tradingName='" . $_dataset ["customer"] ["value"] . "' and primaryCustomer = 1 and useFandVContract = 0 and active = 1;";
						$result = $this->queryDB ( $myQuery );
						if (count ( $result ) != 0) {
							$_dataset ["customer"] ["id"] = intval ( $result [0] ["ID"] );
						} else {
							throw new Exception ( "Error: Customer not found. Please check and amend customer name." );
						}
						
						// Get offloading customer ID
						$myQuery = "select ID from udo_customer where tradingName='" . $_dataset ["offloading customer"] ["value"] . "' and primaryCustomer = 0 and useFandVContract = 0 and active = 1;";
						$result = $this->queryDB ( $myQuery );
						if (count ( $result ) != 0) {
							$_dataset ["offloading customer"] ["id"] = intval ( $result [0] ["ID"] );
						} else {
							$_dataset ["offloading customer"] ["id"] = NULL;
						}
						
						// Get rate type ID
						$myQuery = "select ID from udo_ratetype where name='" . $_dataset ["rate type"] ["value"] . "';";
						$result = $this->queryDB ( $myQuery );
						if (count ( $result ) != 0) {
							$_dataset ["rate type"] ["id"] = intval ( $result [0] ["ID"] );
						} else {
							throw new Exception ( "Error: Rate type not found. Please check and amend rate type name." );
						}
						
						// Get business unit ID
						$myQuery = "select ID from udo_businessunit where name='" . $_dataset ["business unit"] ["value"] . "';";
						$result = $this->queryDB ( $myQuery );
						if (count ( $result ) != 0) {
							$_dataset ["business unit"] ["id"] = intval ( $result [0] ["ID"] );
						} else {
							throw new Exception ( "Error: Business unit not found. Please check and amend business unit name." );
						}
						
						// Get contribution model ID
						$myQuery = "select ID from udo_businessunit where name='" . $_dataset ["contribution model"] ["value"] . "';";
						$result = $this->queryDB ( $myQuery );
						if (count ( $result ) != 0) {
							$_dataset ["contribution model"] ["id"] = intval ( $result [0] ["ID"] );
						} else {
							throw new Exception ( "Error: Contribution model not found." );
						}
						
						// Get IDs for all locations
						$_queries = array ();
						$_queries ["location from town"] = "select ID from udo_location where name='" . $_dataset ["location from town"] ["value"] . "' and _type='udo_City';";
						$_queries ["location from point"] = "select ID from udo_location where name='" . $_dataset ["location from point"] ["value"] . "' and _type='udo_Point';";
						$_queries ["location to town"] = "select ID from udo_location where name='" . $_dataset ["location to town"] ["value"] . "' and _type='udo_City';";
						$_queries ["location to point"] = "select ID from udo_location where name='" . $_dataset ["location to point"] ["value"] . "' and _type='udo_Point';";
						$_queries ["province from"] = "select ID from udo_location where name='" . $_dataset ["province from"] ["value"] . "' and _type='udo_Province';";
						$_queries ["province to"] = "select ID from udo_location where name='" . $_dataset ["province to"] ["value"] . "' and _type='udo_Province';";
						
						foreach ( $_queries as $_sqlKey => $_sqlValue ) {
							$result = $this->queryDB ( $_sqlValue );
							if (count ( $result ) != 0) {
								$_dataset [$_sqlKey] ["id"] = intval ( $result [0] ["ID"] );
							} else {
								$_dataset [$_sqlKey] ["id"] = NULL;
							}
						}
						// : End
						// : Check locations exist and create them if they dont
						try {
							$_locationTree = "";
							$_locationName = "";
							$_parentId = "";
							$_locations = array ();
							$_locations [] = $_dataset ["location from town"];
							$_locations [] = $_dataset ["location to town"];
							$_locations [] = $_dataset ["location from point"];
							$_locations [] = $_dataset ["location to point"];
							$_type = NULL;
							foreach ( $_locations as $_aLocation ) {
								if (! $_aLocation ["id"]) {
									// Store key value of array
									$_locationKey = key ( $_aLocation );
									print_r($_aLocation);
									// : Determine type of location
									if (((strpos ( $_locationKey , "town" )) != FALSE) && ((strpos ( $_locationKey, "from" )) != FALSE)) {
										$_type = "udo_City";
										$_parentId = intval($_dataset["province from"]["id"]);
										$_locationTree = $_dataset["province from"]["value"];
									} else if (((strpos ( $_locationKey, "town" )) != FALSE) && ((strpos ( $_locationKey, "to" )) != FALSE)) {
										$_type = "udo_City";
										$_parentId = intval($_dataset["province to"]["id"]);
										$_locationTree = $_dataset["province to"]["value"];
									} else if (((strpos ( $_locationKey, "point" )) != FALSE) && ((strpos ( $_locationKey, "from" )) != FALSE)) {
										$_type = "udo_Point";
										$_parentId = intval($_dataset["location from town"]["id"]);
										$_locationTree = $_dataset["location from town"]["value"];
									} else if (((strpos ( $_locationKey, "point" )) != FALSE) && ((strpos ( $_locationKey, "to" )) != FALSE)) {
										$_type = "udo_Point";
										$_parentId = intval($_dataset["location to town"]["id"]);
										$_locationTree = $_dataset["location to town"]["value"];
									}
									print($_locationKey . PHP_EOL);
									
									// : End
									// Build string for sql query like search for location
									$_searchStr = "%" . preg_replace ( "@\s@", "%", $_aLocation ["value"] ) . "%";
									$myQuery = "select ID from udo_location where name like '" . $_searchStr . "' and _type='" . $_type . "';";
									$result = $this->queryDB ( $myQuery );
									if (count ( $result ) != 0) {
										$_dataset [$_locationKey] ["id"] = intval ( $result [0] ["ID"] );
									} else {
										
										// : Build location tree
										while (!$_parentId != 0) {
											$treeQuery = "select id, name, parent_id from udo_location where id=" . strval($_parentId) . ";";
											print($treeQuery . PHP_EOL);
											$result = $this->queryDB ( $treeQuery );
											if (count ( $result ) != 0) {
												$_parentId = intval($result [0] ["parent_id"]);
												$_locationName = $result [0] ["name"];
												$_locationTree = $_locationName . " -- " . $_locationTree;
											} else {
												throw new Exception("Cannot find parent for location type: $_type, name: " . $_aLocation["value"]);
											}											
										}
										print_r($_locationTree);
										exit;
										
										// : End
										
										// If location not found then create new location
										switch ($_type) {
											case "udo_City" :
												// : Create City
												$this->_session->open ( $this->_maxurl . self::CITY_URL );
												
												$e = $w->until ( function ($session) {
													return $session->element ( "css selector", "div.toolbar-cell-create" );
												} );
												$this->_session->element ( "css selector", "div.toolbar-cell-create" )->click ();
												
												$e = $w->until ( function ($session) {
													return $session->element ( "xpath", "//*[contains(text(),'Capture the details of City')]" );
												} );
												
												$this->assertElementPresent ( "css selector", "#udo_City-14_0_0_name-14" );
												$this->assertElementPresent ( "css selector", "#udo_City-15__0_parent_id-15" );
												$this->assertElementPresent ( "css selector", "#checkbox_udo_City-2_0_0_active-2" );
												$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
												
												$this->_session->element ( "css selector", "#udo_City-14_0_0_name-14" )->sendKeys ( $city );
												$this->_session->element ( "xpath", "//*[@id='udo_City-15__0_parent_id-15']/option[text()='" . self::PROVINCE . "']" )->click ();
												$this->_session->element ( "css selector", "#checkbox_udo_City-2_0_0_active-2" )->click ();
												$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
												
												$e = $w->until ( function ($session) {
													return $session->element ( "css selector", "div.toolbar-cell-create" );
												} );
												
												$this->_session->element ( "css selector", "div.toolbar-cell-create" )->click ();
												
												$e = $w->until ( function ($session) {
													return $session->element ( "xpath", "//*[contains(text(),'Create Zones - City')]" );
												} );
												
												$this->assertElementPresent ( "css selector", "#udo_ZoneCity_link-5__0_zone_id-5" );
												$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
												
												$zone_id = preg_split ( "/kms.*/", $city );
												
												$this->_session->element ( "xpath", "//*[@id='udo_ZoneCity_link-5__0_zone_id-5']/option[text()='" . $zone_id [0] . "kms Zone " . $_contrib . "']" )->click ();
												$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
												
												$e = $w->until ( function ($session) {
													return $session->element ( "css selector", "div.toolbar-cell-create" );
												} );
												
												$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
												$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
												break;
												// : End Case
											case "udo_Point" :
											case "default" :
												// : Create Point
												$this->_session->open ( $this->_maxurl . self::POINT_URL );
												
												$e = $w->until ( function ($session) {
													return $session->element ( "css selector", "div.toolbar-cell-create" );
												} );
												$this->_session->element ( "css selector", "div.toolbar-cell-create" )->click ();
												
												$e = $w->until ( function ($session) {
													return $session->element ( "xpath", "//*[contains(text(),'Capture the details of Point')]" );
												} );
												
												$this->assertElementPresent ( "css selector", "#udo_Point-14_0_0_name-14" );
												$this->assertElementPresent ( "css selector", "#udo_Point-15__0_parent_id-15" );
												$this->assertElementPresent ( "css selector", "#checkbox_udo_Point-2_0_0_active-2" );
												$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
												
												$this->_session->element ( "css selector", "#udo_Point-14_0_0_name-14" )->sendKeys ( $city );
												$this->_session->element ( "xpath", "//*[@id='udo_Point-15__0_parent_id-15']/option[text()='" . self::PROVINCE . "']" )->click ();
												$this->_session->element ( "css selector", "#checkbox_udo_Point-2_0_0_active-2" )->click ();
												$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
												
												$e = $w->until ( function ($session) {
													return $session->element ( "css selector", "div.toolbar-cell-create" );
												} );
												break;
												// : End Case
										}
									}
								}
							}
						} catch ( Exception $e ) {
							// Add code here
						}
						exit;
						// : End
						
						// : Check locations are linked to customer
						try {
							
							// Get all currently open windows
							$_winAll = $this->_session->window_handles ();
							// Set window focus to main window
							$this->_session->focusWindow ( $_winAll [0] );
							// If there is more than 1 window open then close all but main window
							if (count ( $_winAll ) > 1) {
								$this->clearWindows ();
							}
							
							$recordExists = FALSE;
							$pointname = preg_replace ( "/–/", "-", $pointname );
							$this->lastRecord = $point . " (" . $pointname . ")";
							
							// : Check if offloading customer and link exist and store result in $recordExists variable
							$myQuery = preg_replace ( "/%s/", $point . " (" . $pointname . ")", $this->_myqueries [5] );
							$myQuery = preg_replace ( "/%t/", "udo_Point", $myQuery );
							$result = $this->queryDB ( $myQuery );
							if (count ( $result ) != 0) {
								$myQuery = preg_replace ( "/%n/", $point . " (" . $pointname . ")", $this->_myqueries [0] );
								$myQuery = preg_replace ( "/%t/", $_customer, $myQuery );
								$result = $this->queryDB ( $myQuery );
								if (count ( $result ) != 0) {
									$recordExists = TRUE;
								} else {
									$recordExists = FALSE;
								}
							} else {
								$recordExists = FALSE;
							}
							// : End
							
							// Load MAX customer page
							if (! $recordExists) {
								
								$this->_session->open ( $this->_maxurl . self::CUSTOMER_URL . $customer_id );
								// Wait for element = #subtabselector
								$e = $w->until ( function ($session) {
									return $session->element ( "css selector", "#subtabselector" );
								} );
								// Select option from select box
								$this->_session->element ( "xpath", "//*[@id='subtabselector']/select/option[text()='Locations']" )->click ();
								// Wait for element = #button-create
								$e = $w->until ( function ($session) {
									return $session->element ( "css selector", "#button-create" );
								} );
								// Click element - button
								$this->_session->element ( "css selector", "#button-create" )->click ();
								// Wait for element
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//*[@id='udo_CustomerLocations-5__0_location_id-5']" );
								} );
								$this->assertElementPresent ( "link text", "Create Location" );
								$this->assertElementPresent ( "xpath", "//*[@id='udo_CustomerLocations-5__0_location_id-5']" );
								$this->_session->element ( "link text", "Create Location" )->click ();
								
								// Select New Window
								$_winAll = $this->_session->window_handles ();
								if (count ( $_winAll > 1 )) {
									$this->_session->focusWindow ( $_winAll [1] );
								} else {
									throw new Exception ( "ERROR: Window not present" );
								}
								
								$e = $w->until ( function ($session) {
									return $session->element ( "css selector", "#udo_Location-17__0__type-17" );
								} );
								$this->assertElementPresent ( "css selector", "#udo_Location-17__0__type-17" );
								$this->assertElementPresent ( "css selector", "input[name=save][type=submit]" );
								$this->_session->element ( "xpath", "//*[@id='udo_Location-17__0__type-17']/option[text()='Point']" )->click ();
								$this->_session->element ( "css selector", "input[name=save][type=submit]" )->click ();
								// Wait for element = Page heading
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//*[contains(text(),'Capture the details of Point')]" );
								} );
								// Assert all elements on current page are present
								try {
									$this->assertElementPresent ( "xpath", "//*[@id='udo_Point-14_0_0_name-14']" );
									$this->assertElementPresent ( "xpath", "//*[@id='udo_Point-15__0_parent_id-15']" );
									$this->assertElementPresent ( "xpath", "//*[@id='udo_Point-32_0_0_pointType_id-32[2]']" );
									$this->assertElementPresent ( "xpath", "//*[@id='checkbox_udo_Point-2_0_0_active-2']" );
									$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
									// Enter name of new location in text field
									$this->_session->element ( "xpath", "//*[@id='udo_Point-14_0_0_name-14']" )->sendKeys ( $point . " (" . $pointname . ")" );
									// Select parent location from select box
									$this->_session->element ( "xpath", "//*[@id='udo_Point-15__0_parent_id-15']/option[text()='" . self::PROVINCE . " -- " . $pointname . "']" )->click ();
									// Check the offloading point checkbox
									$this->_session->element ( "xpath", "//*[@id='udo_Point-32_0_0_pointType_id-32[2]']" )->click ();
									// Check the active checkbox
									$this->_session->element ( "xpath", "//*[@id='checkbox_udo_Point-2_0_0_active-2']" )->click ();
									// Click the submit button
									$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
								} catch ( Exception $e ) {
									print ($e->getMessage ()) ;
									exit ();
								}
								
								// Select Parent Window
								if (count ( $_winAll > 1 )) {
									$this->_session->focusWindow ( $_winAll [0] );
								}
								// Wait for element
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//*[contains(text(),'Capture the details of Customer Locations')]" );
								} );
								$this->assertElementPresent ( "xpath", "//*[@id='udo_CustomerLocations-5__0_location_id-5']" );
								$this->assertElementPresent ( "xpath", "//*[@id='udo_CustomerLocations-8__0_type-8']" );
								$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
								
								// Select new location from select box
								$this->_session->element ( "xpath", "//*[@id='udo_CustomerLocations-5__0_location_id-5']/option[text()='" . $point . " (" . $pointname . ")" . "']" )->click ();
								// Select offloading as type for new location from select box
								$this->_session->element ( "xpath", "//*[@id='udo_CustomerLocations-8__0_type-8']/option[text()='Offloading']" )->click ();
								// Click the submit button
								$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
								
								// : Create Business Unit Link for Point Link
								$myQuery = preg_replace ( "/%n/", $point . " (" . $pointname . ")", $this->_myqueries [0] );
								$myQuery = preg_replace ( "/%t/", $_customer, $myQuery );
								$result = $this->queryDB ( $myQuery );
								if (count ( $result ) != 0) {
									$location_id = $result [0] ["ID"];
									$this->_session->open ( $this->_maxurl . self::LOCATION_BU_URL . $location_id );
									// Wait for element
									$e = $w->until ( function ($session) {
										return $session->element ( "css selector", "#button-create" );
									} );
									// Click element = button-create
									$this->_session->element ( "css selector", "#button-create" )->click ();
									
									// Wait for element = Page heading
									$e = $w->until ( function ($session) {
										return $session->element ( "xpath", "//*[contains(text(),'Create Customer Locations - Business Unit')]" );
									} );
									
									$this->assertElementPresent ( "xpath", "//*[@id='udo_CustomerLocationsBusinessUnit_link-2__0_businessUnit_id-2']" );
									$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
									
									$this->_session->element ( "xpath", "//*[@id='udo_CustomerLocationsBusinessUnit_link-2__0_businessUnit_id-2']/option[text()='" . $_bu . "']" )->click ();
									// Click the submit button
									$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
								} else {
									throw new Exception ( "Could not find customer location record: " . $point . " (" . $pointname . ")" );
								}
							}
						} catch ( Exception $e ) {
							// Add code here
						}
						
						// : End
						
						// : Check offloading customer exists
						
						try {
							// : Load customer data browser page for Customer
							$this->_session->open ( $this->_maxurl . self::CUSTOMER_URL . $customer_id );
							
							// : Create and link Offloading Customer
							
							// Wait for element = Page heading
							$e = $w->until ( function ($session) {
								return $session->element ( "css selector", "#subtabselector" );
							} );
							$this->_session->element ( "xpath", "//*[@id='subtabselector']/select/option[text()='Offloading Customers where Customer is " . $_customer . "']" )->click ();
							
							// Wait for element = Page heading
							$e = $w->until ( function ($session) {
								return $session->element ( "css selector", "#button-create" );
							} );
							$this->_session->element ( "css selector", "#button-create" )->click ();
							
							// Wait for element = Page heading
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[contains(text(),'Capture the details of Offloading Customers')]" );
							} );
							
							$this->assertElementPresent ( "link text", "Create Customer" );
							$this->_session->element ( "link text", "Create Customer" )->click ();
							
							// Select New Window
							$_winAll = $this->_session->window_handles ();
							if (count ( $_winAll > 1 )) {
								$this->_session->focusWindow ( $_winAll [1] );
							} else {
								throw new Exception ( "ERROR: Window not present" );
							}
							
							// Wait for element = Page heading
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[@id='udo_Customer-22_0_0_tradingName-22']" );
							} );
							
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Customer-22_0_0_tradingName-22']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Customer-12_0_0_legalName-12']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Customer-32_0_0_customerType_id-32[11]']" );
							$this->assertElementPresent ( "xpath", "//*[@id='checkbox_udo_Customer-2_0_0_active-2']" );
							$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
							
							$this->_session->element ( "xpath", "//*[@id='udo_Customer-22_0_0_tradingName-22']" )->sendKeys ( $point . " (" . $pointname . ")" );
							$this->_session->element ( "xpath", "//*[@id='udo_Customer-12_0_0_legalName-12']" )->sendKeys ( $point . " (" . $pointname . ")" );
							$this->_session->element ( "xpath", "//*[@id='udo_Customer-32_0_0_customerType_id-32[11]']" )->click ();
							$this->_session->element ( "xpath", "//*[@id='checkbox_udo_Customer-2_0_0_active-2']" )->click ();
							$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
							
							if (count ( $_winAll > 1 )) {
								$this->_session->focusWindow ( $_winAll [0] );
							}
							
							// Wait for element = Page heading
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[contains(text(),'Capture the details of Offloading Customers')]" );
							} );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_OffloadingCustomers-3__0_customer_id-3']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_OffloadingCustomers-6__0_offloadingCustomer_id-6']" );
							$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
							
							$this->_session->element ( "xpath", "//*[@id='udo_OffloadingCustomers-3__0_customer_id-3']/option[text()='" . $_customer . "']" )->click ();
							$this->_session->element ( "xpath", "//*[@id='udo_OffloadingCustomers-6__0_offloadingCustomer_id-6']/option[text()='" . $point . " (" . $pointname . ")" . "']" )->click ();
							$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
							
							// : Create Business Unit Link for Offloading Customer Link
							$myQuery = preg_replace ( "/%o/", $point . " (" . $pointname . ")", $this->_myqueries [1] );
							$myQuery = preg_replace ( "/%t/", $_customer, $myQuery );
							$result = $this->queryDB ( $myQuery );
							if (count ( $result ) != 0) {
								$offloadingcustomer_id = $result [0] ["ID"];
								$this->_session->open ( $this->_maxurl . self::OFF_CUST_BU_URL . $offloadingcustomer_id );
								
								// Wait for element = #subtabselector
								$e = $w->until ( function ($session) {
									return $session->element ( "css selector", "#subtabselector" );
								} );
								$this->_session->element ( "xpath", "//*[@id='subtabselector']/select/option[text()='Offloading Customers - Business Unit']" )->click ();
								
								// Wait for element = #button-create
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//div[@id='button-create']" );
								} );
								$this->_session->element ( "xpath", "//div[@id='button-create']" )->click ();
								
								// Wait for element = Page Heading
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//*[contains(text(),'Create Offloading Customers - Business Unit')]" );
								} );
								
								$this->assertElementPresent ( "xpath", "//*[@id='udo_OffloadingCustomersBusinessUnit_link-2__0_businessUnit_id-2']" );
								$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
								
								$this->_session->element ( "xpath", "//*[@id='udo_OffloadingCustomersBusinessUnit_link-2__0_businessUnit_id-2']/option[text()='" . $_bu . "']" )->click ();
								$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
								
								// Wait for element = #button-create
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//div[@id='button-create']" );
								} );
							} else {
								throw new Exception ( "Could not find offloading customer record: " . $point . " (" . $pointname . ")" );
							}
						} catch ( Exception $e ) {
							// Add code here
						}
						// : End
						
						// : Check offloading customer is linked to the customer
						
						try {
							// : Load customer data browser page for Customer
							$this->_session->open ( $this->_maxurl . self::CUSTOMER_URL . $customer_id );
							
							// : Create and link Offloading Customer
							
							// Wait for element = Page heading
							$e = $w->until ( function ($session) {
								return $session->element ( "css selector", "#subtabselector" );
							} );
							$this->_session->element ( "xpath", "//*[@id='subtabselector']/select/option[text()='Offloading Customers where Customer is " . $_customer . "']" )->click ();
							
							// Wait for element = Page heading
							$e = $w->until ( function ($session) {
								return $session->element ( "css selector", "#button-create" );
							} );
							$this->_session->element ( "css selector", "#button-create" )->click ();
							
							// Wait for element = Page heading
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[contains(text(),'Capture the details of Offloading Customers')]" );
							} );
							
							$this->assertElementPresent ( "link text", "Create Customer" );
							$this->_session->element ( "link text", "Create Customer" )->click ();
							
							// Select New Window
							$_winAll = $this->_session->window_handles ();
							if (count ( $_winAll > 1 )) {
								$this->_session->focusWindow ( $_winAll [1] );
							} else {
								throw new Exception ( "ERROR: Window not present" );
							}
							
							// Wait for element = Page heading
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[@id='udo_Customer-22_0_0_tradingName-22']" );
							} );
							
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Customer-22_0_0_tradingName-22']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Customer-12_0_0_legalName-12']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Customer-32_0_0_customerType_id-32[11]']" );
							$this->assertElementPresent ( "xpath", "//*[@id='checkbox_udo_Customer-2_0_0_active-2']" );
							$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
							
							$this->_session->element ( "xpath", "//*[@id='udo_Customer-22_0_0_tradingName-22']" )->sendKeys ( $point . " (" . $pointname . ")" );
							$this->_session->element ( "xpath", "//*[@id='udo_Customer-12_0_0_legalName-12']" )->sendKeys ( $point . " (" . $pointname . ")" );
							$this->_session->element ( "xpath", "//*[@id='udo_Customer-32_0_0_customerType_id-32[11]']" )->click ();
							$this->_session->element ( "xpath", "//*[@id='checkbox_udo_Customer-2_0_0_active-2']" )->click ();
							$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
							
							if (count ( $_winAll > 1 )) {
								$this->_session->focusWindow ( $_winAll [0] );
							}
							
							// Wait for element = Page heading
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[contains(text(),'Capture the details of Offloading Customers')]" );
							} );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_OffloadingCustomers-3__0_customer_id-3']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_OffloadingCustomers-6__0_offloadingCustomer_id-6']" );
							$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
							
							$this->_session->element ( "xpath", "//*[@id='udo_OffloadingCustomers-3__0_customer_id-3']/option[text()='" . $_customer . "']" )->click ();
							$this->_session->element ( "xpath", "//*[@id='udo_OffloadingCustomers-6__0_offloadingCustomer_id-6']/option[text()='" . $point . " (" . $pointname . ")" . "']" )->click ();
							$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
							
							// : Create Business Unit Link for Offloading Customer Link
							$myQuery = preg_replace ( "/%o/", $point . " (" . $pointname . ")", $this->_myqueries [1] );
							$myQuery = preg_replace ( "/%t/", $_customer, $myQuery );
							$result = $this->queryDB ( $myQuery );
							if (count ( $result ) != 0) {
								$offloadingcustomer_id = $result [0] ["ID"];
								$this->_session->open ( $this->_maxurl . self::OFF_CUST_BU_URL . $offloadingcustomer_id );
								
								// Wait for element = #subtabselector
								$e = $w->until ( function ($session) {
									return $session->element ( "css selector", "#subtabselector" );
								} );
								$this->_session->element ( "xpath", "//*[@id='subtabselector']/select/option[text()='Offloading Customers - Business Unit']" )->click ();
								
								// Wait for element = #button-create
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//div[@id='button-create']" );
								} );
								$this->_session->element ( "xpath", "//div[@id='button-create']" )->click ();
								
								// Wait for element = Page Heading
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//*[contains(text(),'Create Offloading Customers - Business Unit')]" );
								} );
								
								$this->assertElementPresent ( "xpath", "//*[@id='udo_OffloadingCustomersBusinessUnit_link-2__0_businessUnit_id-2']" );
								$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
								
								$this->_session->element ( "xpath", "//*[@id='udo_OffloadingCustomersBusinessUnit_link-2__0_businessUnit_id-2']/option[text()='" . $_bu . "']" )->click ();
								$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
								
								// Wait for element = #button-create
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//div[@id='button-create']" );
								} );
							} else {
								throw new Exception ( "Could not find offloading customer record: " . $point . " (" . $pointname . ")" );
							}
						} catch ( Exception $e ) {
							// Add code here
						}
						
						// : End
						
						// : Check if route exists
						
						// : End
						
						// : Check if route and rate exists for customer
						
						try {
							
							// Fix hyphen conversion problem which occurs when importing data from xls
							$point = preg_replace ( "/–/", "-", $point );
							$pointname = preg_replace ( "/–/", "-", $pointname );
							// Store current record been processed for use with error reporting
							$this->lastRecord = $point . " (" . $pointname . ")";
							
							// Get all currently open windows
							$_winAll = $this->_session->window_handles ();
							// Set window focus to main window
							$this->_session->focusWindow ( $_winAll [0] );
							// If there is more than 1 window open then close all but main window
							if (count ( $_winAll ) > 1) {
								$this->clearWindows ();
							}
							
							// : Get kms zone for this entry
							$kms = preg_split ( "/kms Zone.*/", $pointname );
							$kms = $kms [0];
							// : End
							// Load the MAX customer page
							$this->_session->open ( $this->_maxurl . self::CUSTOMER_URL . $customer_id );
							
							// Wait for element = #subtabselector
							$e = $w->until ( function ($session) {
								return $session->element ( "css selector", "#subtabselector" );
							} );
							// Select Rates from the select box
							$this->_session->element ( "xpath", "//*[@id='subtabselector']/select/option[text()='Rates']" )->click ();
							
							// : Run SQL Query to check if route and rate already has been created
							$myQuery = preg_replace ( "/%f/", $point, $this->_myqueries [3] );
							$myQuery = preg_replace ( "/%t/", $pointname, $myQuery );
							$myQuery = preg_replace ( "/%g/", $objectregistry_id, $myQuery );
							$myQuery = preg_replace ( "/%c/", $customer_id, $myQuery );
							$myQuery = preg_replace ( "/%d/", $trucktype_id, $myQuery );
							$myQuery = preg_replace ( "/%m/", $_contrib, $myQuery );
							$myQuery = preg_replace ( "/%b/", $bunit_id, $myQuery );
							$myQuery = preg_replace ( "/%r/", $rateType_id, $myQuery );
							$result = $this->queryDB ( $myQuery );
							
							// : End
							
							if (count ( $result ) == 0) {
								
								// Wait for element = #button-create
								$e = $w->until ( function ($session) {
									return $session->element ( "css selector", "#button-create" );
								} );
								// Click element - #button-create
								$this->_session->element ( "css selector", "#button-create" )->click ();
								
								// Wait for element Page Heading
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//*[@id='udo_Rates-31__0_route_id-31']" );
								} );
								// Assert element on page - link: Create Route
								$this->assertElementPresent ( "link text", "Create Route" );
								// Click element - link: Create Route
								$this->_session->element ( "link text", "Create Route" )->click ();
								// Select New Window
								$_allWin = $this->_session->window_handles ();
								if (count ( $_allWin > 1 )) {
									$this->_session->focusWindow ( $_allWin [1] );
								} else {
									throw new Exception ( "ERROR: Window not present." );
								}
								try {
									// Wait for element Page Heading
									$e = $w->until ( function ($session) {
										return $session->element ( "xpath", "//*[@id='udo_Route-6__0_locationFrom_id-6']" );
									} );
									
									// : Assert all elements on page
									$this->assertElementPresent ( "xpath", "//*[@id='udo_Route-7__0_locationTo_id-7']" );
									$this->assertElementPresent ( "xpath", "//*[@id='udo_Route-4_0_0_expectedKms-4']" );
									$this->assertElementPresent ( "xpath", "//*[@id='udo_Route-3_0_0_duration-3']" );
									$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
									// : End
									
									try {
										$this->_session->element ( "xpath", "//*[@id='udo_Route-6__0_locationFrom_id-6']/option[text()='$point']" )->click ();
									} catch ( PHPWebDriver_NoSuchElementWebDriverError $e ) {
										throw new Exception ( "ERROR: Could not find the location from on the create route page" . PHP_EOL . $e->getMessage () );
									}
									
									$this->_session->element ( "xpath", "//*[@id='udo_Route-7__0_locationTo_id-7']/option[text()='" . $pointname . "']" )->click ();
									$this->_session->element ( "xpath", "//*[@id='udo_Route-4_0_0_expectedKms-4']" )->sendKeys ( $kms );
									// Calculate duration from kms value at 60K/H
									$duration = strval ( number_format ( (floatval ( $kms ) / 80) * 60, 0, "", "" ) );
									$this->_session->element ( "xpath", "//*[@id='udo_Route-3_0_0_duration-3']" )->sendKeys ( $duration );
									$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
									
									if (count ( $_allWin > 1 )) {
										$this->_session->focusWindow ( $_allWin [0] );
									}
									
									// Wait for element Page Heading
									$e = $w->until ( function ($session) {
										return $session->element ( "xpath", "//*[@id='udo_Rates-31__0_route_id-31']" );
									} );
									
									$this->assertElementPresent ( "xpath", "//*[@id='udo_Rates-30__0_rateType_id-30']" );
									$this->assertElementPresent ( "xpath", "//*[@id='udo_Rates-4__0_businessUnit_id-4']" );
									$this->assertElementPresent ( "xpath", "//*[@id='udo_Rates-36__0_truckDescription_id-36']" );
									$this->assertElementPresent ( "xpath", "//*[@id='udo_Rates-20__0_model-20']" );
									$this->assertElementPresent ( "xpath", "//*[@id='checkbox_udo_Rates-15_0_0_enabled-15']" );
									$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
									
									$this->_session->element ( "xpath", "//*[@id='udo_Rates-31__0_route_id-31']/option[text()='" . $point . " TO " . $pointname . "']" )->click ();
									$this->_session->element ( "xpath", "//*[@id='udo_Rates-30__0_rateType_id-30']/option[text()='$_rateType']" )->click ();
									$this->_session->element ( "xpath", "//*[@id='udo_Rates-4__0_businessUnit_id-4']/option[text()='" . $_bu . "']" )->click ();
									$this->_session->element ( "xpath", "//*[@id='udo_Rates-36__0_truckDescription_id-36']/option[text()='$_truckType']" )->click ();
									$this->_session->element ( "xpath", "//*[@id='udo_Rates-20__0_model-20']/option[text()='" . $_contrib . "']" )->click ();
									$this->_session->element ( "xpath", "//*[@id='checkbox_udo_Rates-15_0_0_enabled-15']" )->click ();
									$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
								} catch ( Exception $e ) {
									print ($e->getMessage () . PHP_EOL) ;
									exit ();
								}
								// Wait for element = #button-create
								try {
									$e = $w->until ( function ($session) {
										return $session->element ( "css selector", "#button-create" );
									} );
								} catch ( PHPWebDriver_NoSuchElementWebDriverError $e ) {
									throw new Exception ( "ERROR: Could not find the create button after post of create new rate process." . PHP_EOL . $e->getMessage () );
								}
								
								// : Create Rate Value for Route
								$myQuery = preg_replace ( "/%f/", $point, $this->_myqueries [3] );
								$myQuery = preg_replace ( "/%t/", $pointname, $myQuery );
								$myQuery = preg_replace ( "/%g/", $objectregistry_id, $myQuery );
								$myQuery = preg_replace ( "/%c/", $customer_id, $myQuery );
								$myQuery = preg_replace ( "/%d/", $trucktype_id, $myQuery );
								$myQuery = preg_replace ( "/%m/", $_contrib, $myQuery );
								$myQuery = preg_replace ( "/%b/", $bunit_id, $myQuery );
								$myQuery = preg_replace ( "/%r/", $rateType_id, $myQuery );
								$result = $this->queryDB ( $myQuery );
								if (count ( $result ) != 0) {
									$rate_id = $result [0] ["ID"];
									$rateurl = preg_replace ( "/%s/", $rate_id, $this->_maxurl . self::RATEVAL_URL );
									$this->_session->open ( $rateurl );
									
									// Wait for element = #button-create
									$e = $w->until ( function ($session) {
										return $session->element ( "css selector", "#button-create" );
									} );
									// Click element - #button-create
									$this->_session->element ( "css selector", "#button-create" )->click ();
									
									// Wait for element = #button-create
									$e = $w->until ( function ($session) {
										return $session->element ( "xpath", "//*[contains(text(),'Create Date Range Values')]" );
									} );
									
									$this->assertElementPresent ( "xpath", "//*[@id='DateRangeValue-2_0_0_beginDate-2']" );
									$this->assertElementPresent ( "xpath", "//*[@id='DateRangeValue-20_0_0_value-20']" );
									$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
									
									$this->_session->element ( "xpath", "//*[@id='DateRangeValue-2_0_0_beginDate-2']" )->clear ();
									$this->_session->element ( "xpath", "//*[@id='DateRangeValue-2_0_0_beginDate-2']" )->sendKeys ( $_startDate );
									$productname = preg_split ( "/^" . $kms . "kms Zone /", $pointname );
									$ratevalue = strval ( (number_format ( floatval ( $routes [$kms] [$productname [1]] ), 2, ".", "" )) );
									$this->_session->element ( "xpath", "//*[@id='DateRangeValue-20_0_0_value-20']" )->sendKeys ( $ratevalue );
									$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
								} else {
									throw new Exception ( "ERROR: Could not find newly created rate record." );
								}
							}
						} catch ( Exception $e ) {
							$_msg = preg_replace ( "@/s@", "creating", self::PROCESS_FAIL );
							$_msg = preg_replace ( "@/o@", "route and rate", $_msg );
							$_msg = preg_replace ( "@/o@", "dummy data", $_msg );
							throw new Exception ( $_msg );
						}
						
						// : End
						
						// : End
					} catch ( Exception $e ) {
						echo "Error: " . $e->getMessage () . PHP_EOL;
						echo "Time of error: " . date ( "Y-m-d H:i:s" ) . PHP_EOL;
						echo "Last record: " . $this->lastRecord;
						$this->takeScreenshot ();
						$_erCount = count ( $this->_error );
						$this->_error [$_erCount + 1] ["error"] = $e->getMessage ();
						$this->_error [$_erCount + 1] ["record"] = $this->lastRecord;
						$this->_error [$_erCount + 1] ["type"] = "Rate";
					}
				}
				// : End
			} else {
				// Add code here
			}
			
			// : Tear Down
			$this->_session->element ( 'xpath', "//*[contains(@href,'/logout')]" )->click ();
			// Wait for page to load and for elements to be present on page
			$e = $w->until ( function ($session) {
				return $session->element ( 'css selector', 'input[id=identification]' );
			} );
			$this->assertElementPresent ( 'css selector', 'input[id=identification]' );
			
			// : Close connection to database
			$db = null;
			if ($_dbStatus) {
				$this->closeDB ();
			}
			// : End
			
			// Terminate session
			$this->_session->close ();
			// : End
			
			// : If errors occured. Create xls of entries that failed.
			if (count ( $this->_error ) != 0) {
				$_xlsfilename = (dirname ( __FILE__ ) . $this->_errDir . self::DS . date ( "Y-m-d_His_" ) . "MAXLive_" . ".xlsx");
				$this->writeExcelFile ( $_xlsfilename, $this->_error, $_xlsColumns );
				if (file_exists ( $_xlsfilename )) {
					print ("Excel error report written successfully to file: $_xlsfilename") ;
				} else {
					print ("Excel error report write unsuccessful") ;
				}
			}
			// : End
		} else {
			throw new Exception ( $this->getFunctionErrorMsg () );
		}
	}
	
	// : Private Functions
	
	/**
	 * MAXLive_Subcontractors::writeExcelFile($excelFile, $excelData)
	 * Create, Write and Save Excel Spreadsheet from collected data obtained from the variance report
	 *
	 * @param $excelFile, $excelData        	
	 */
	public function writeExcelFile($excelFile, $excelData, $columns) {
		try {
			// Check data validility
			if (count ( $excelData ) != 0) {
				
				// : Create new PHPExcel object
				print ("<pre>") ;
				print (date ( 'H:i:s' ) . " Create new PHPExcel object" . PHP_EOL) ;
				$objPHPExcel = new PHPExcel ();
				// : End
				
				// : Set properties
				print (date ( 'H:i:s' ) . " Set properties" . PHP_EOL) ;
				$objPHPExcel->getProperties ()->setCreator ( self::XLS_CREATOR );
				$objPHPExcel->getProperties ()->setLastModifiedBy ( self::XLS_CREATOR );
				$objPHPExcel->getProperties ()->setTitle ( self::XLS_TITLE );
				$objPHPExcel->getProperties ()->setSubject ( self::XLS_SUBJECT );
				// : End
				
				// : Setup Workbook Preferences
				print (date ( 'H:i:s' ) . " Setup workbook preferences" . PHP_EOL) ;
				$objPHPExcel->getDefaultStyle ()->getFont ()->setName ( 'Arial' );
				$objPHPExcel->getDefaultStyle ()->getFont ()->setSize ( 8 );
				$objPHPExcel->getActiveSheet ()->getPageSetup ()->setOrientation ( PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE );
				$objPHPExcel->getActiveSheet ()->getPageSetup ()->setPaperSize ( PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4 );
				$objPHPExcel->getActiveSheet ()->getPageSetup ()->setFitToWidth ( 1 );
				$objPHPExcel->getActiveSheet ()->getPageSetup ()->setFitToHeight ( 0 );
				// : End
				
				// : Set Column Headers
				$alphaVar = range ( 'A', 'Z' );
				print (date ( 'H:i:s' ) . " Setup column headers" . PHP_EOL) ;
				
				$i = 0;
				foreach ( $columns as $key ) {
					$objPHPExcel->getActiveSheet ()->setCellValue ( $alphaVar [$i] . "1", $key );
					$objPHPExcel->getActiveSheet ()->getStyle ( $alphaVar [$i] . '1' )->getFont ()->setBold ( true );
					$i ++;
				}
				
				// : End
				
				// : Add data from $excelData array
				print (date ( 'H:i:s' ) . " Add data from error array" . PHP_EOL) ;
				$rowCount = ( int ) 2;
				$objPHPExcel->setActiveSheetIndex ( 0 );
				foreach ( $excelData as $values ) {
					$i = 0;
					foreach ( $values as $key => $value ) {
						$objPHPExcel->getActiveSheet ()->getCell ( $alphaVar [$i] . strval ( $rowCount ) )->setValueExplicit ( $value, PHPExcel_Cell_DataType::TYPE_STRING );
						$i ++;
					}
					$rowCount ++;
				}
				// : End
				
				// : Setup Column Widths
				for($i = 0; $i <= count ( $columns ); $i ++) {
					$objPHPExcel->getActiveSheet ()->getColumnDimension ( $alphaVar [$i] )->setAutoSize ( true );
				}
				// : End
				
				// : Rename sheet
				print (date ( 'H:i:s' ) . " Rename sheet" . PHP_EOL) ;
				$objPHPExcel->getActiveSheet ()->setTitle ( self::XLS_TITLE );
				// : End
				
				// : Save spreadsheet to Excel 2007 file format
				print (date ( 'H:i:s' ) . " Write to Excel2007 format" . PHP_EOL) ;
				print ("</pre>" . PHP_EOL) ;
				$objWriter = new PHPExcel_Writer_Excel2007 ( $objPHPExcel );
				$objWriter->save ( $excelFile );
				$objPHPExcel->disconnectWorksheets ();
				unset ( $objPHPExcel );
				unset ( $objWriter );
				// : End
			} else {
				print ("<pre>") ;
				print_r ( "ERROR: The function was passed an empty array" );
				print ("</pre>") ;
				exit ();
			}
		} catch ( Exception $e ) {
			echo "Caught exception: ", $e->getMessage (), "\n";
			exit ();
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
	private function openDB($dsn, $username, $password, $options) {
		try {
			$this->_db = new PDO ( $dsn, $username, $password, $options );
		} catch ( PDOException $ex ) {
			return FALSE;
		}
	}
	
	/**
	 * MAXLive_Subcontractors::takeScreenshot()
	 * This is a function description for a selenium test function
	 *
	 * @param object: $_session        	
	 */
	private function takeScreenshot() {
		$_img = $this->_session->screenshot ();
		$_data = base64_decode ( $_img );
		$_file = dirname ( __FILE__ ) . $this->_scrDir . DIRECTORY_SEPARATOR . date ( "Y-m-d_His" ) . "_WebDriver.png";
		$_success = file_put_contents ( $_file, $_data );
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
	private function assertElementPresent($_using, $_value) {
		$e = $this->_session->element ( $_using, $_value );
		$this->assertEquals ( count ( $e ), 1 );
	}
	
	/**
	 * MAXLive_Rates_Create::assertElementPresent($_title)
	 * This functions switches focus between each of the open windows
	 * and looks for the first window where the page title matches
	 * the given title and returns true else false
	 *
	 * @param string: $_title        	
	 * @param
	 *        	boolean: return
	 */
	private function selectWindow($_title) {
		try {
			$_results = ( array ) array ();
			// Store the current window handle value
			$_currentWin = $this->_session->window_handle ();
			// Get all open windows handles
			$e = $this->_session->window_handles ();
			if (count ( $e ) > 1) {
				foreach ( $e as $_browserWindow ) {
					$this->_session->focusWindow ( $_browserWindow );
					$_page_title = $this->_session->title ();
					preg_match ( "/^.+" . $_title . ".+/", $_page_title, $_results );
					if ((count ( $_results ) != 0) && ($_browserWindow != $_currentWin)) {
						return true;
					}
				}
			}
			$this->_session->focusWindow ( $_currentWin );
			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}
	
	/**
	 * MAXLive_Rates_Create::closeDB()
	 * Close connection to Database
	 */
	private function closeDB() {
		$this->_db = null;
	}
	
	/**
	 * MAXLive_Rates_Create::queryDB($sqlquery)
	 * Pass MySQL Query to database and return output
	 *
	 * @param string: $sqlquery        	
	 * @param array: $result        	
	 */
	private function queryDB($sqlquery) {
		try {
			$result = $this->_db->query ( $sqlquery );
			return $result->fetchAll ( PDO::FETCH_ASSOC );
		} catch ( PDOException $ex ) {
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
	private function clearWindows() {
		$_winAll = $this->_session->window_handles ();
		$_curWin = $this->_session->window_handle ();
		foreach ( $_winAll as $_win ) {
			if ($_win != $_curWin) {
				$this->_session->focusWindow ( $_win );
				$this->_session->deleteWindow ();
			}
		}
		$this->_session->focusWindow ( $_curWin );
	}
	
	/**
	 * MAXLive_Rates_Create::ImportCSVFileIntoArray($csvFile)
	 * From supplied csv file save data into multidimensional array
	 *
	 * @param string: $csvFile        	
	 * @param array: $_result        	
	 */
	private function ImportCSVFileIntoArray($csvFile) {
		try {
			$_data = ( array ) array ();
			$_header = NULL;
			if (file_exists ( $csvFile )) {
				if (($_handle = fopen ( $csvFile, 'r' )) !== FALSE) {
					while ( ($_row = fgetcsv ( $_handle, self::CSV_LIMIT, self::DELIMITER, self::ENCLOSURE )) !== FALSE ) {
						if (! $_header) {
							foreach ( $_row as $_value ) {
								$_header [] = strtolower ( $_value );
							}
						} else {
							$_data [] = array_combine ( $_header, $_row );
						}
					}
					fclose ( $_handle );
					
					if (count ( $_data ) != 0) {
						return $_data;
					} else {
						$_msg = preg_replace ( "@%s@", $csvFile, self::FILE_EMPTY );
						throw new Exception ( $_msg );
					}
				} else {
					$_msg = preg_replace ( "@%s@", $csvFile, self::COULD_NOT_OPEN_FILE );
					throw new Exception ( $_msg );
				}
			} else {
				$_msg = preg_replace ( "@%s@", $csvFile, self::FILE_NOT_FOUND );
				throw new Exception ( $_msg );
			}
		} catch ( Exception $e ) {
			$this->_functionError = $e->getMessage ();
			return FALSE;
		}
	}
	
	// : End
}