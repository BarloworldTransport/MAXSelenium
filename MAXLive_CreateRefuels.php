<?php
// Set error reporting level for this script
error_reporting ( E_ALL );

// : Includes

require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
require_once 'PullDataFromMySQLQuery.php';
require_once 'FileParser.php';

// : End

/**
 * MAXLive_CreateRefuels.php
 *
 * @package MAXLive_CreateRefuels
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
class MAXLive_CreateRefuels extends PHPUnit_Framework_TestCase {
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
	protected $_data = array ();
	protected $_config = array ();
	protected $_datadir;
	protected $_errdir;
	protected $_scrdir;
	protected $_report_dir;
	protected $_tmpVar;
	protected $_errors = array ();
	protected $_tmp;
	protected $_rules_on;
	protected $_rules_off;
	protected $_page_timeout;
	protected $_element_timeout;
	
	// : Public Functions
	
	/**
	 * MAXLive_CreateRefuels::getReportFileName()
	 * Use script name and date to build filename for the error report
	 *
	 * @return string: $_file
	 */
	public function getReportFileName() {
		$_ext = ".php";
		$_result = preg_split ( "/\./", __FILE__ );
		if ($_result) {
			$_ext = $_result [count ( $_result ) - 1];
		}
		$_file = date ( 'Y-m-d_H:i:s' ) . basename ( __FILE__, ".php" );
		return $_file;
	}
	// : Accessors
	// : End
	
	// : Magic
	/**
	 * MAXLive_CreateRefuels::__construct()
	 * Class constructor
	 */
	public function __construct() {
		$ini = dirname ( realpath ( __FILE__ ) ) . self::DS . "ini" . self::DS . self::INI_FILE;
		
		if (is_file ( $ini ) === FALSE) {
			echo "No " . self::INI_FILE . " file found. Please create it and populate it with the following data: username=x@y.com, password=`your password`, your name shown on MAX the welcome page welcome=`Joe Soap` and mode=`test` or `live`" . PHP_EOL;
			return FALSE;
		}
		$data = parse_ini_file ( $ini );
		if ((array_key_exists ( "pagetimeout", $data ) && $data ["pagetimeout"]) && (array_key_exists ( "elementtimeout", $data ) && $data ["elementtimeout"]) && (array_key_exists ( "datadir", $data ) && $data ["datadir"]) && (array_key_exists ( "screenshotdir", $data ) && $data ["screenshotdir"]) && (array_key_exists ( "errordir", $data ) && $data ["errordir"]) && (array_key_exists ( "file1", $data ) && $data ["file1"]) && (array_key_exists ( "username", $data ) && $data ["username"]) && (array_key_exists ( "password", $data ) && $data ["password"]) && (array_key_exists ( "welcome", $data ) && $data ["welcome"]) && (array_key_exists ( "mode", $data ) && $data ["mode"]) && (array_key_exists ( "wdport", $data ) && $data ["wdport"]) && (array_key_exists ( "proxy", $data ) && $data ["proxy"]) && (array_key_exists ( "browser", $data ) && $data ["browser"]) && ((array_key_exists("reportdir", $data)) && ($data["reportdir"])) && ((array_key_exists("ruleson", $data)) && ($data["ruleson"])) && ((array_key_exists("rulesoff", $data)) && ($data["rulesoff"]))) {
			$this->_username = $data ["username"];
			$this->_password = $data ["password"];
			$this->_welcome = $data ["welcome"];
			$this->_mode = $data ["mode"];
			$this->_wdport = $data ["wdport"];
			$this->_proxyip = $data ["proxy"];
			$this->_browser = $data ["browser"];
			$this->_datadir = $data ["datadir"];
			$this->_scrdir = $data ["screenshotdir"];
			$this->_errdir = $data ["errordir"];
			$this->_report_dir = $data ["reportdir"];
			$this->_file1 = $data ["file1"];
			$this->_rules_on = $data["ruleson"];
			$this->_rules_off = $data["rulesoff"];
			$this->_page_timeout = $data["pagetimeout"];
			$this->_element_timeout = $data["elementtimeout"];
			switch ($this->_mode) {
				case "live" :
					$this->_maxurl = self::LIVE_URL;
					break;
				default :
					$this->_maxurl = self::TEST_URL;
			}
		} else {
			echo "The correct data is not present in user_data.ini. Please confirm. Fields are username, password, welcome and mode" . PHP_EOL;
			return FALSE;
		}
	}
	
	/**
	 * MAXLive_CreateRefuels::__destruct()
	 * Class destructor
	 * Allow for garbage collection
	 */
	public function __destruct() {
		unset ( $this );
	}
	// : End
	public function setUp() {
		// This would be the url of the host running the server-standalone.jar
		$wd_host = "http://localhost:$this->_wdport/wd/hub";
		self::$driver = new PHPWebDriver_WebDriver ( $wd_host );
		if (! $this->_proxyip) {
			$this->_session = self::$driver->session ( $this->_browser );
		} else {
			$desired_capabilities = array ();
			$proxy = new PHPWebDriver_WebDriverProxy ();
			$proxy->httpProxy = $this->_proxyip;
			$proxy->add_to_capabilities ( $desired_capabilities );
			$this->_session = self::$driver->session ( $this->_browser, $desired_capabilities );
		}
	}
	
	/**
	 * MAXLive_CreateRefuels::testFunctionTemplate
	 * This is a function description for a selenium test function
	 */
	public function testCreateRefuels() {
		// Create new object for included class to import data
		
		// : Connect to SQL Server
		if (! $_sqldb = new PullDataFromMySQLQuery ( "max2", "192.168.1.19" )) {
			$_dberr = $_sqldb->getErrors ();
			$_errmsg = ( string ) "";
			if (! $_dberr) {
				foreach ( $_dberr as $key => $value ) {
					$_errmsg .= $value . PHP_EOL;
				}
				if ($_errmsg) {
					$_err = preg_replace ( "/%s/", $_errmsg, self::DB_ERROR );
				}
			} else {
				$_err = self::DB_ERROR;
			}
			throw new Exception ( $_err );
		}
		// : End
		
		// : Queries to run throughout script
		$_queries = ( array ) array (
				"select f.name from udo_fleettrucklink as ftl left join udo_truck as t on (t.id=ftl.truck_id) left join udo_fleet as f on (f.id=ftl.fleet_id) left join daterangevalue as drv on (drv.objectInstanceId=ftl.id) where f.name!='Entire Active Fleet' and t.id=%s and drv.type='FleetTruckLink' and (drv.endDate IS NULL or drv.endDate > DATE(CONCAT(CURDATE(), ' 00:00:00')));",
				"select id, fleetnum from udo_truck where fleetnum='%s';",
				"select d.nickname, CONCAT(p.first_name, ' ', p.last_name) as fullname, d.staffNumber from udo_driver as d left join person as p on (p.id=d.person_id) where d.staffNumber = '%s';",
				"select r.id, ron.orderNumber from udo_refuel as r left join udo_refuelordernumber as ron on (ron.id=r.refuelOrderNumber_id) where ron.orderNumber=%s;",
		        "select count(ftl.truck_id) as linked_trucks, f.name from udo_fleettrucklink as ftl left join udo_fleet as f on (f.id=ftl.fleet_id) left join udo_truck as t on (t.id=ftl.truck_id) left join daterangevalue as drv on (drv.objectInstanceId=ftl.id) where ftl.fleet_id IN (select ftl.fleet_id from udo_fleettrucklink as ftl left join udo_truck as t on (t.id=ftl.truck_id) left join udo_fleet as f on (f.id=ftl.fleet_id) left join daterangevalue as drv on (drv.objectInstanceId=ftl.id) where f.name!='Entire Active Fleet' and t.id=%s and drv.type='FleetTruckLink' and (drv.endDate IS NULL or drv.endDate > DATE(CONCAT(CURDATE(), ' 00:00:00')))) and drv.type='FleetTruckLink' and (drv.endDate IS NULL or drv.endDate > DATE(CONCAT(CURDATE(), ' 00:00:00'))) group by ftl.fleet_id order by linked_trucks;" 
		);
		// : End
		
		// Build file path string
		$_file1 = realpath($this->_datadir) . self::DS . $this->_file1;
		
		// : Import CSV files data
		if (file_exists ( $_file1 )) {
			$_csvfile = new FileParser ( $_file1 );
			$_refuelData = $_csvfile->parseFile ();
			unset ( $_csvfile );
		}
		
		// : End
		
		// : Prepare data to be processed
		if ($_refuelData) {
			foreach ( $_refuelData as $recordKey => $recordValue ) {
				foreach ( $recordValue as $itemKey => $itemValue ) {
					$this->_data [$recordKey] [$_refuelData [0] [$itemKey]] = $itemValue;
				}
			}
		}
		// : End
		
		try {
			// Initialize session
			$session = $this->_session;
			$this->_session->setPageLoadTimeout ( intval($this->_page_timeout) );
			$w = new PHPWebDriver_WebDriverWait ( $session, intval($this->_element_timeout) );
			
			// : Log into MAX
			// Load MAX home page
			$this->_session->open ( $this->_maxurl );
			
			// : Wait for page to load and for elements to be present on page
			$e = $w->until ( function ($session) {
				return $session->element ( 'css selector', "#contentFrame" );
			} );
			
			$iframe = $this->_session->element ( 'css selector', '#contentFrame' );
			$this->_session->switch_to_frame ( $iframe );
			
			$e = $w->until ( function ($session) {
				return $session->element ( 'css selector', 'input[id=identification]' );
			} );
			// : End
			
			// : Assert element present
			$this->assertElementPresent ( 'css selector', 'input[id=identification]' );
			$this->assertElementPresent ( 'css selector', 'input[id=password]' );
			$this->assertElementPresent ( 'css selector', 'input[name=submit][type=submit]' );
			// : End
			
			// Send keys to input text box
			$e = $this->_session->element ( 'css selector', 'input[id=identification]' )->sendKeys ( $this->_username );
			// Send keys to input text box
			$e = $this->_session->element ( 'css selector', 'input[id=password]' )->sendKeys ( $this->_password );
			
			// Click login button
			$this->_session->element ( 'css selector', 'input[name=submit][type=submit]' )->click ();
			// Switch out of frame
			$this->_session->switch_to_frame ();
			
			// : Wait for page to load and for elements to be present on page
			$e = $w->until ( function ($session) {
				return $session->element ( 'css selector', "#contentFrame" );
			} );
			$iframe = $this->_session->element ( 'css selector', '#contentFrame' );
			$this->_session->switch_to_frame ( $iframe );
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[text()='" . $this->_welcome . "']" );
			} );
			$this->assertElementPresent ( "xpath", "//*[text()='" . $this->_welcome . "']" );
			// Switch out of frame
			$this->_session->switch_to_frame ();
			// : End
			
			// : Load Planningboard to rid of iframe loading on every page from here on
			$this->_session->open ( $this->_maxurl . self::PB_URL );
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]" );
			} );
			// : End
		} catch ( Exception $e ) {
			$_errmsg = preg_replace ( "/%h/", $this->_maxurl, self::LOGIN_FAIL );
			$_errmsg = preg_replace ( "/%h/", $e->getMessage (), $_errmsg );
			throw new Exception ( $_errmsg );
			unset ( $_errmsg );
		}
		
		// : Turn off refuel update actions
		if (strtolower($this->_rules_off) == "true" || $this->_rules_off == "1") {
		try {
			$this->_session->open ( $this->_maxurl . self::ADMIN_URL );
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//a[contains(text(),'Refuel') and contains(@href,'/DataBrowser?browsePrimaryObject=')]" );
			} );
			
			$this->_session->element ( "xpath", "//a[contains(text(),'Refuel') and contains(@href,'/DataBrowser?browsePrimaryObject=')]" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[@id='toolbar']/div[contains(text(),'Refuel')]" );
			} );
			
			// : Update errorOdo_maximum refuel update action
			$this->assertElementPresent ( "xpath", "//*[@id='subtabselector']/select" );
			$this->_session->element ( "xpath", "//*[@id='subtabselector']/select/option[text()='Update']" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//a[contains(@href,'ObjectRegistry=641&ObjectCrudActionError_id=40&ObjectRegistry_id=403') and @class='edit' and @title='Update']" );
			} );
			
			$this->_session->element ( "xpath", "//a[contains(@href,'ObjectRegistry=641&ObjectCrudActionError_id=40&ObjectRegistry_id=403') and @class='edit' and @title='Update']" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
			} );
			
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" );
			$this->assertElementPresent ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" );
			$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
			
			// : Check if we are updating the correct refuel update action
			$_actionName = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" )->attribute ( "value" );
			if ($_actionName != "errorOdo_maximum") {
				$errmsg = preg_replace ( "/%s/", "errorOdo_maximum", self::NOT_CORRECT_ACTION );
				throw new Exception ( $errmsg );
			}
			// : End
			
			$_actStage = $this->getSelectedOptionValue ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']/option" );
			$_actOp = $this->getSelectedOptionValue ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']/option" );
			$_actStatus = $this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->attribute ( "checked" );
			
			if ($_actStage == "Pre" && $_actOp == "Update" && $_actStatus) {
				$this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->click ();
			}
			
			$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
			// : End
			
			// : Update errorOdo_previous refuel update action
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//a[contains(@href,'ObjectCrudActionError_id=42&ObjectRegistry_id=403&returnurl=/DataBrowser') and @class='edit' and @title='Update']" );
			} );
			
			$this->_session->element ( "xpath", "//a[contains(@href,'ObjectCrudActionError_id=42&ObjectRegistry_id=403&returnurl=/DataBrowser') and @class='edit' and @title='Update']" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
			} );
			
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" );
			$this->assertElementPresent ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" );
			$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
			
			// : Check if we are updating the correct refuel update action
			$_actionName = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" )->attribute ( "value" );
			if ($_actionName != "errorOdo_previous") {
				$errmsg = preg_replace ( "/%s/", "errorOdo_previous", self::NOT_CORRECT_ACTION );
				throw new Exception ( $errmsg );
			}
			// : End
			
			$_actStage = $this->getSelectedOptionValue ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']/option" );
			$_actOp = $this->getSelectedOptionValue ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']/option" );
			$_actStatus = $this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->attribute ( "checked" );
			
			if ($_actStage == "Pre" && $_actOp == "Update" && $_actStatus) {
				$this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->click ();
			}
			
			$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
			// : End
			
			// : Load Planningboard to rid of iframe loading on every page from here on
			$this->_session->open ( $this->_maxurl . self::PB_URL );
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]" );
			} );
			// : End
		} catch ( Exception $e ) {
			throw new Exception ( "Could not continue. Failed to update Refuel Update Actions before starting to run the script.\n Reason:{$e->getMessage()}" );
		}
		}
		// : End
		
		// : End
		
		// : Main Loop
		foreach ( $this->_data as $recKey => $recVal ) {
			try {
				// : Reset variables
				$_truckid = "";
				$_fleetname = "";
				// : End
				
				// : Set main window to default and close all windows if there is more than one open
				$_winAll = $this->_session->window_handles ();
				// Set window focus to main window
				$this->_session->focusWindow ( $_winAll [0] );
				// If there is more than 1 window open then close all but main window
				if (count ( $_winAll ) > 1) {
					$this->clearWindows ();
				}
				// : End
				
				// : If record missing information then throw exception and skip record
				if (! $recVal ["Truck"] || ! $recVal ["Odo"] || ! $recVal ["Location"] || ! $recVal ["Date"] || ! $recVal ["Litres"]) {
					$_recErr = "Record#: {$recKey}, Truck: {$recVal["Truck"]}, Odo: {$recVal["Odo"]}, Location: {$recVal["Location"]}, Date: {$recVal["Date"]}, Litres: {$recVal["Litres"]}";
					throw new Exception ( "Incomplete date for record:" . $_recErr );
				}
				// : End
				
				// : Run SQL Query to check whether truck exists on MAX
				$_query = preg_replace ( "/%s/", $recVal ["Truck"], $_queries [1] );
				$_result = $_sqldb->getDataFromQuery ( $_query );
				if ($_result) {
					// : Check if truck is linked to a fleet and if so get the first fleet returned in the query results
					$_truckid = $_result [0] ["id"];
					$_query = preg_replace ( "/%s/", $_truckid, $_queries [4] );
					$_result2 = $_sqldb->getDataFromQuery ( $_query );
					if ($_result2) {
						$_fleetname = $_result2 [0] ["name"];
						$_counttrucks = $_result2 [0] ["linked_trucks"];
					}
					// : End
				}
				
				$_query = preg_replace ( "/%s/", $recVal ["Driver"], $_queries [2] );
				$_result = $_sqldb->getDataFromQuery ( $_query );
				if ($_result) {
					$_driver = "{$_result[0]["nickname"]} [{$_result[0]["fullname"]}]";
					$_staffNo = intval ( $_result [0] ["staffNumber"] );
					
					// : Kaluma defaults to STAFFNUMBER value if in driver select box for refuel page when staffNumber <> int value
					if (! $_staffNo) {
						$_staffNo = "STAFFNUMBER";
					}
					// : End
					
					$_driver .= " [{$_staffNo}]";
				} else {
					$_driver = "Unknown Driver [Unknown Driver] [STAFFNUMBER]";
				}
				// : End
				
				if ($_truckid && $_fleetname) {
					$_initial = FALSE;
					$this->_tmp = $_fleetname;
					// : Load the fleet to which the truck is linked too
					$e = $w->until ( function ($session) {
						return $session->element ( "xpath", "//*[@id='fplanningboard']/table/tbody/tr[2]/td[1]/select/option[contains(text(),'{$this->_tmp}')]" );
					} );
					$this->_session->element ( "xpath", "//*[@id='fplanningboard']/table/tbody/tr[2]/td[1]/select/option[contains(text(),'{$_fleetname}')]" )->click ();
					// : End
					
					// : Check for the presence of the truck on the Planningboard
					$this->_tmp = $_truckid;
					$e = $w->until ( function ($session) {
						return $session->element ( "xpath", "//a[contains(@href,'truck_id={$this->_tmp}') and contains(@href,'refuel{$this->_tmp}') and contains(@href, 'ObjectRegistry=udo_Refuel')]" );
					} );
					// : End
					
					// Click the F for refuel
					$_fStatus = $this->_session->element ( "xpath", "//a[contains(@href,'truck_id={$_truckid}') and contains(@href,'refuel{$_truckid}') and contains(@href, 'ObjectRegistry=udo_Refuel')]/span[2]" )->attribute ( 'style' );
					preg_match ( "/red|green/", $_fStatus, $_matches );
					if ($_matches) {
						$_fStatus = $_matches [0];
					}
					
					$this->_session->element ( "xpath", "//a[contains(@href,'truck_id={$_truckid}') and contains(@href,'refuel{$_truckid}') and contains(@href, 'ObjectRegistry=udo_Refuel')]" )->click ();
					
					// Select New Window
					$_winAll = $this->_session->window_handles ();
					if (count ( $_winAll > 1 )) {
						$this->_session->focusWindow ( $_winAll [1] );
					} else {
						throw new Exception ( "ERROR: Window not present" );
					}
					
					if ($_fStatus) {
						try {
							
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[contains(text(),'Initial Refuel Capture')]" );
							} );
							$_initial = TRUE;
						} catch ( Exception $e ) {
							try {
								// : Check if the refuel stage is complete refuel process
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//*[contains(text(),'Complete Refuel Capture')]" );
								} );
							} catch ( Exception $e ) {
								try {
									// : Click continue on Display Order Number page
									$e = $w->until ( function ($session) {
										return $session->element ( "xpath", "//*[contains(text(),'Display Order Number')]" );
									} );
									$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
									$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
									$_initial = TRUE;
									// : End
								} catch ( Exception $e ) {
									try {
										// : Click Save & Continue to complete the refuel on the Memo page
										$e = $w->until ( function ($session) {
											return $session->element ( "xpath", "//*[contains(text(),'Memo')]" );
										} );
										$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
										$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
										$_initial = TRUE;
										// : End
									} catch ( Exception $e ) {
										throw new Exception ( "F is red and cannot find determine initial or complete stage of refuel process.\n{$e->getMessage()}" );
									}
								}
							}
						}
					}
					
					if ($_initial) {
						try {
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[contains(text(),'Initial Refuel Capture')]" );
							} );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-33__0_refuelPoint-33']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-22__0_truck_id-22']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-6__0_driver_id-6']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-9_0_0_fillDateTime-9']" );
							$this->assertElementPresent ( "xpath", "//*[@id='formfield']/textarea" );
							$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
							
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-33__0_refuelPoint-33']/option[text()='{$recVal["Location"]}']" )->click ();
							
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-6__0_driver_id-6']/option[text()='{$_driver}']" )->click ();
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-9_0_0_fillDateTime-9']" )->clear ();
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-9_0_0_fillDateTime-9']" )->sendKeys ( $recVal ["Date"] );
							$this->_session->element ( "xpath", "//*[@id='formfield']/textarea" )->clear ();
							$this->_session->element ( "xpath", "//*[@id='formfield']/textarea" )->sendKeys ( "This refuel was created by an automation script. Reference no: {$recVal["Note"]}" );
							$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
							
							// : Click continue on Display Order Number page
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[contains(text(),'Display Order Number')]" );
							} );
							$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
							$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
							// : End
							
							// : Select Parent Window
							if (count ( $_winAll > 1 )) {
								$this->_session->focusWindow ( $_winAll [0] );
							}
							// : End
							
							// : Check for the presence of the truck on the Planningboard
							$this->_tmp = $_truckid;
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//a[contains(@href,'truck_id={$this->_tmp}') and contains(@href,'refuel{$this->_tmp}') and contains(@href, 'ObjectRegistry=udo_Refuel')]" );
							} );
							// : End
							
							// : Get the refuel F link style color
							$_fStatus = $this->_session->element ( "xpath", "//a[contains(@href,'truck_id={$_truckid}') and contains(@href,'refuel{$_truckid}') and contains(@href, 'ObjectRegistry=udo_Refuel')]/span[2]" )->attribute ( 'style' );
							preg_match ( "/red|green/", $_fStatus, $_matches );
							if ($_matches) {
								$_fStatus = $_matches [0];
							}
							
							// If style color for F refuel link is red then continue
							if ($_fStatus == "red") {
								$this->_session->element ( "xpath", "//a[contains(@href,'truck_id={$_truckid}') and contains(@href,'refuel{$_truckid}') and contains(@href, 'ObjectRegistry=udo_Refuel')]" )->click ();
								
								// : Select New Window
								$_winAll = $this->_session->window_handles ();
								if (count ( $_winAll > 1 )) {
									$this->_session->focusWindow ( $_winAll [1] );
								} else {
									throw new Exception ( "ERROR: Window not present" );
								}
								// : End
							}
						} catch ( Exception $e ) {
							try {
								// : Check if the refuel stage is complete refuel process
								$e = $w->until ( function ($session) {
									return $session->element ( "xpath", "//*[contains(text(),'Complete Refuel Capture')]" );
								} );
							} catch ( Exception $e ) {
								$_initial = FALSE;
							}
						}
					}
					if ($_fStatus == "red") {
						$e = $w->until ( function ($session) {
							return $session->element ( "xpath", "//*[contains(text(),'Complete Refuel Capture')]" );
						} );
						// : Confirm refuel order is for the correct truck and order by confirming the details
						try {
							$this->assertElementPresent ( "xpath", "//*/tbody/tr/td[contains(text(),'{$recVal["Truck"]}')]" );
						} catch ( Exception $e ) {
							throw new Exception ( "Could not confirm that the order been completed was the correct order. Error message: " . $e->getMessage () );
						}
						// : End
						
						// : Check all elements for entering and selecting values to complete refuel are present
						$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-16_0_0_odo-16']" );
						
						$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-13_0_0_litres-13']" );
						$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-3_0_0_cost-3']" );
						$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-10__0_full_or_Partial-10']" );
						$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
						// : End
						
						// Store the refuel order number
						$_refuelOrder = $this->_session->element ( "xpath", "//*[@id='udo_Refuel-21_0_0_refuelOrderNumber_id-21']/tbody/tr/td[1]" )->text ();
						if ($_refuelOrder) {
							$this->_data [$recKey] ["OrderNumber"] = $_refuelOrder;
						}
						// : End
						
						// : Enter values into the Complete Refuel form
						$this->_session->element ( "xpath", "//*[@id='udo_Refuel-16_0_0_odo-16']" )->clear ();
						$this->_session->element ( "xpath", "//*[@id='udo_Refuel-16_0_0_odo-16']" )->sendKeys ( $recVal ["Odo"] );
						$this->_session->element ( "xpath", "//*[@id='udo_Refuel-13_0_0_litres-13']" )->clear ();
						
						$this->_session->element ( "xpath", "//*[@id='udo_Refuel-13_0_0_litres-13']" )->sendKeys ( $recVal ["Litres"] );
						$this->_session->element ( "xpath", "//*[@id='udo_Refuel-3_0_0_cost-3']" )->clear ();
						$this->_session->element ( "xpath", "//*[@id='udo_Refuel-3_0_0_cost-3']" )->sendKeys ( $recVal ["Cost"] );
						$this->_session->element ( "xpath", "//*[@id='udo_Refuel-10__0_full_or_Partial-10']/option[text()='{$recVal["FullPartial"]}']" )->click ();
						
						$_note = $this->_session->element ( "xpath", "//*[@id='formfield']/textarea" )->text ();
						if (! $_note) {
							$this->_session->element ( "xpath", "//*[@id='formfield']/textarea" )->clear ();
							$this->_session->element ( "xpath", "//*[@id='formfield']/textarea" )->sendKeys ( "This refuel was created by an automation script. Reference no: {$recVal["Note"]}" );
						}
						$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
						
						// : Click Save & Continue to complete the refuel on the Memo page
						$e = $w->until ( function ($session) {
							return $session->element ( "xpath", "//*[contains(text(),'Memo')]" );
						} );
						$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
						$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
						// : End
						
						// : End
						
						// : Construct array data to add refuel order number and status of each refuel create process
						// : End
						
						// : Select Parent Window
						if (count ( $_winAll > 1 )) {
							$this->_session->focusWindow ( $_winAll [0] );
						}
						// : End
						
						// : Check F refuel link for truck exists and that is style color has changed to green
						$this->_tmp = $_truckid;
						$e = $w->until ( function ($session) {
							return $session->element ( "xpath", "//a[contains(@href,'truck_id={$this->_tmp}') and contains(@href,'refuel{$this->_tmp}') and contains(@href, 'ObjectRegistry=udo_Refuel')]/span[contains(@style,'green')]" );
						} );
						// : End
					}
				}
			} catch ( Exception $e ) {
				// : Add details of record when error occured to error array
				$_num = count ( $this->_errors ) + 1;
				foreach ( $recVal as $key => $value ) {
					$this->_errors [$_num] [$key] = $value;
				}
				$this->_errors [$_num] ["errormsg"] = $e->getMessage ();
				$_scrshotfn = realpath($this->_scrdir) . self::DS . date("Y-m-d_H:i:s") . $recVal["Truck"] . substr($e->getMessage(), 1, 10);
				$this->takeScreenshot ( $this->_session, $_scrshotfn );
				// : End
			}
		}
		
		// : Turn on refuel update actions
		
		if (strtolower($this->_rules_on) == "true" || $this->_rules_on == "1") {
		try {
			
			// : Set main window to default and close all windows if there is more than one open
			$_winAll = $this->_session->window_handles ();
			// Set window focus to main window
			$this->_session->focusWindow ( $_winAll [0] );
			// If there is more than 1 window open then close all but main window
			if (count ( $_winAll ) > 1) {
				$this->clearWindows ();
			}
			// : End
			
			$this->_session->open ( $this->_maxurl . self::ADMIN_URL );
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//a[contains(text(),'Refuel') and contains(@href,'/DataBrowser?browsePrimaryObject=')]" );
			} );
			
			$this->_session->element ( "xpath", "//a[contains(text(),'Refuel') and contains(@href,'/DataBrowser?browsePrimaryObject=')]" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[@id='toolbar']/div[contains(text(),'Refuel')]" );
			} );
			
			// : Update errorOdo_maximum refuel update action
			$this->assertElementPresent ( "xpath", "//*[@id='subtabselector']/select" );
			$this->_session->element ( "xpath", "//*[@id='subtabselector']/select/option[text()='Update']" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//a[contains(@href,'ObjectRegistry=641&ObjectCrudActionError_id=40&ObjectRegistry_id=403') and @class='edit' and @title='Update']" );
			} );
			
			$this->_session->element ( "xpath", "//a[contains(@href,'ObjectRegistry=641&ObjectCrudActionError_id=40&ObjectRegistry_id=403') and @class='edit' and @title='Update']" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
			} );
			
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" );
			$this->assertElementPresent ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" );
			$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
			
			// : Check if we are updating the correct refuel update action
			$_actionName = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" )->attribute ( "value" );
			if ($_actionName != "errorOdo_maximum") {
				$errmsg = preg_replace ( "/%s/", "errorOdo_maximum", self::NOT_CORRECT_ACTION );
				throw new Exception ( $errmsg );
			}
			// : End
			
			$_actStage = $this->getSelectedOptionValue ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']/option" );
			$_actOp = $this->getSelectedOptionValue ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']/option" );
			$_actStatus = $this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->attribute ( "checked" );
			
			if ($_actStage == "Pre" && $_actOp == "Update" && ! $_actStatus) {
				$this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->click ();
			}
			
			$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
			// : End
			
			// : Update errorOdo_previous refuel update action
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//a[contains(@href,'ObjectCrudActionError_id=42&ObjectRegistry_id=403&returnurl=/DataBrowser') and @class='edit' and @title='Update']" );
			} );
			
			$this->_session->element ( "xpath", "//a[contains(@href,'ObjectCrudActionError_id=42&ObjectRegistry_id=403&returnurl=/DataBrowser') and @class='edit' and @title='Update']" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
			} );
			
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" );
			$this->assertElementPresent ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" );
			$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
			
			// : Check if we are updating the correct refuel update action
			$_actionName = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" )->attribute ( "value" );
			if ($_actionName != "errorOdo_previous") {
				$errmsg = preg_replace ( "/%s/", "errorOdo_previous", self::NOT_CORRECT_ACTION );
				throw new Exception ( $errmsg );
			}
			// : End
			
			$_actStage = $this->getSelectedOptionValue ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']/option" );
			$_actOp = $this->getSelectedOptionValue ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']/option" );
			$_actStatus = $this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->attribute ( "checked" );
			
			if ($_actStage == "Pre" && $_actOp == "Update" && ! $_actStatus) {
				$this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->click ();
			}
			
			$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
			// : End
			
			// : Load Planningboard to rid of iframe loading on every page from here on
			$this->_session->open ( $this->_maxurl . self::PB_URL );
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]" );
			} );
			// : End
		} catch ( Exception $e ) {
			throw new Exception ( "Could not continue. Failed to update Refuel Update Actions before starting to run the script.\n Reason:{$e->getMessage()}" );
		}
		}
		// : End
		try {	
		// : Report errors if any occured
		if ($this->_errors) {
			$_errfile =  realpath($this->_errdir) . self::DS . $this->getReportFileName () . ".csv";
			$this->ExportToCSV ( $_errfile, $this->_errors );
			echo "Exported error report to the following path and file: " . $_errfile;
		}
		} catch (Exception $e) {
			// : Add some error handling code here
		}
		// : End
		try {
		// : Report all successful completed refuel orders
		$_orders = ( array ) array ();
		foreach ( $this->_data as $key => $value ) {
			$_orders [$key] ["id"] = $key;
			if (array_key_exists ( "OrderNumber", $value )) {
				$_orders [$key] = $value;
			}
		}
		if ($_orders) {
			$_ordersfile = realpath($this->_report_dir) . self::DS . $this->getReportFileName () . ".csv";
			$this->ExportToCSV ( $_ordersfile, $_orders );
			echo "Exported successfully created refuels report to the following path and file: " . $_ordersfile;
		}
		} catch (Exception $e) {
			
			// Add some error handling code here
		}
		// : End
		
		// : Tear Down
		// Click the logout link
		$this->_session->open ( $this->_maxurl . self::PB_URL );
		$e = $w->until ( function ($session) {
			return $session->element ( "xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]" );
		} );
		$this->_session->element ( 'xpath', "//*[contains(@href,'/logout')]" )->click ();
		// Wait for page to load and for elements to be present on page
		$e = $w->until ( function ($session) {
			return $session->element ( 'css selector', 'input[id=identification]' );
		} );
		$this->assertElementPresent ( 'css selector', 'input[id=identification]' );
		// Terminate session
		$this->_session->close ();
		// : End
	}
        
        // : Private Functions
    
    /**
     * MAXLive_CreateRefuels::takeScreenshot($_session)
     * This is a function description for a selenium test function
     *
     * @param object: $_session            
     */
    private function takeScreenshot($_session, $_filename)
    {
        try {
            $_img = $_session->screenshot();
            $_data = base64_decode($_img);
            $_success = file_put_contents($_filename, $_data);
            if ($_success) {
                return $_filename;
            } else {
                return FALSE;
            }
        } catch (Exception $e) {
            $this->_errors[] = "ERROR: Failed taking a screenshot: " . $e->getMessage();
        }
    }
	
	/**
	 * MAXLive_CreateRefuels::assertElementPresent($_using, $_value)
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
	 * MAXLive_CreateRefuels::getSelectedOptionValue($_using, $_value)
	 * This is a function description for a selenium test function
	 *
	 * @param string: $_using        	
	 * @param string: $_value        	
	 */
	private function getSelectedOptionValue($_using, $_value) {
		try {
			$_result = FALSE;
			$_cnt = count ( $this->_session->elements ( $_using, $_value ) );
			for($x = 1; $x <= $_cnt; $x ++) {
				$_selected = $this->_session->element ( $_using, $_value . "[$x]" )->attribute ( "selected" );
				if ($_selected) {
					$_result = $this->_session->element ( $_using, $_value . "[$x]" )->attribute ( "value" );
					break;
				}
			}
		} catch ( Exception $e ) {
			$_result = FALSE;
		}
		return ($_result);
	}
	
	/**
	 * MAXLive_CreateRefuels::clearWindows()
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
	 * MAXLive_CreateRefuels::ExportToCSV($csvFile, $arr)
	 * From supplied csv file save data into multidimensional array
	 *
	 * @param string: $csvFile        	
	 * @param array: $_arr        	
	 */
	private function ExportToCSV($csvFile, $_arr) {
		try {
			$_data = ( array ) array ();
			if (file_exists ( dirname ( $csvFile ) )) {
				$_handle = fopen ( $csvFile, 'w' );
				foreach ( $_arr as $key => $value ) {
					fputcsv ( $_handle, $value );
				}
				fclose ( $_handle );
			} else {
				$_msg = preg_replace ( "@%s@", $csvFile, self::DIR_NOT_FOUND );
				throw new Exception ( $_msg );
			}
		} catch ( Exception $e ) {
			return FALSE;
		}
	}
	// : End
}
