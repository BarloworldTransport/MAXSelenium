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
	protected $_tmpVar;
	protected $_errors = array ();
	protected $_tmp;
	
	// : Public Functions
	
	/**
	 * MAXLive_CreateRefuels::getErrorReportFileName()
	 * Get the scriptname and return the filename without the file extension
	 *
	 * @return string: $_thisScriptName
	 */
	public function getScriptName() {
		$_thisScriptName = preg_split ( "/\./", __FILE__ );
		if ($_thisScriptName) {
			if ($_thisScriptName [1] === "php") {
				$_thisScriptName = $_thisScriptName [0];
			} else {
				unset ( $_thisScriptName );
			}
		}
		if ($_thisScriptName) {
			return $_thisScriptName;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * MAXLive_CreateRefuels::getErrorReportFileName()
	 * Use script name and date to build filename for the error report
	 *
	 * @return string: $_file
	 */
	public function getErrorReportFileName() {
		$_file = "error_report_";
		$_scriptName = $this->getScriptName ();
		$_file .= $_scriptName . date ( "Y-m-d H:i:s" );
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
		if ((array_key_exists ( "datadir", $data ) && $data ["datadir"]) && (array_key_exists ( "screenshotdir", $data ) && $data ["screenshotdir"]) && (array_key_exists ( "errordir", $data ) && $data ["errordir"]) && (array_key_exists ( "file1", $data ) && $data ["file1"]) && (array_key_exists ( "file2", $data ) && $data ["file2"]) && (array_key_exists ( "username", $data ) && $data ["username"]) && (array_key_exists ( "password", $data ) && $data ["password"]) && (array_key_exists ( "welcome", $data ) && $data ["welcome"]) && (array_key_exists ( "mode", $data ) && $data ["mode"]) && (array_key_exists ( "wdport", $data ) && $data ["wdport"]) && (array_key_exists ( "proxy", $data ) && $data ["proxy"]) && (array_key_exists ( "browser", $data ) && $data ["browser"])) {
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
			$this->_file1 = $data ["file1"];
			$this->_file2 = $data ["file2"];
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
				"select f.name from udo_fleettrucklink as ftl left join udo_truck as t on (t.id=ftl.truck_id) left join udo_fleet as f on (f.id=ftl.fleet_id) where t.id=%s;",
				"select id, fleetnum from udo_truck where fleetnum='%s';" 
		);
		// : End
		
		// Build file path string
		$_file1 = dirname ( __FILE__ ) . self::DS . $this->_datadir . self::DS . $this->_file1;
		$_file2 = dirname ( __FILE__ ) . self::DS . $this->_datadir . self::DS . $this->_file2;
		
		// : Import CSV files data
		if (file_exists ( $_file1 )) {
			$_csvfile = new FileParser ( $_file1 );
			$_refuelData = $_csvfile->parseFile ();
			unset ( $_csvfile );
		}
		
		if (file_exists ( $_file2 )) {
			$_csvfile = new FileParser ( $_file2 );
			$_refuelConfig = $_csvfile->parseFile ();
			unset ( $_csvfile );
		}
		// : End
		
		// : Prepare data to be processed
		if ($_refuelData) {
			$_count = count ( $_refuelData [0] ) - 1;
			foreach ( $_refuelData as $key => $value ) {
				if ($key != 0) {
					for($x = 1; $x < $_count; $x ++) {
						$this->_data [$value [0]] [$_refuelData [0] [$x]] = $value [$x];
					}
				}
			}
		}
		
		if ($_refuelConfig) {
			foreach ( $_refuelConfig as $key => $value ) {
				$this->_config [$value [0]] = $value [1];
			}
		}
		unset ( $_refuelConfig );
		unset ( $_refuelData );
		// : End
		
		try {
			// Initialize session
			$session = $this->_session;
			$this->_session->setPageLoadTimeout ( 60 );
			$w = new PHPWebDriver_WebDriverWait ( $session );
			
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
		try {
			$this->_session->open ( $this->_maxurl . self::ADMIN_URL );
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//a[text()='Refuel ']" );
			} );
			
			$this->_session->element ( "xpath", "//a[text()='Refuel ']" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[@id='toolbar']/div[contains(text(),'Refuel')]" );
			} );
			
			// : Update errorOdo_maximum refuel update action
			$this->assertElementPresent ( "xpath", "//*[@id='subtabselector']/select" );
			$this->_session->element ( "xpath", "//*[@id='subtabselector']/select/option[text()='Update']" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//a[contains(@href,'/process?handle=ObjectCrudActionError_update__Process__20050101090000&ObjectRegistry=641&ObjectCrudActionError_id=40&ObjectRegistry_id=403') and @class='edit' and @title='Update']" );
			} );
			
			$this->_session->element ( "xpath", "//a[contains(@href,'/process?handle=ObjectCrudActionError_update__Process__20050101090000&ObjectRegistry=641&ObjectCrudActionError_id=40&ObjectRegistry_id=403') and @class='edit' and @title='Update']" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[contains(text(),'Update Object Crud Action Error')]" );
			} );
			
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" );
			$this->assertElementPresent ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" );
			$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
			
			// : Check if we are updating the correct refuel update action
			$e = $w->until ( function ($session) {
				$this->_session->element ( "xpath", "//input[@id='ObjectCrudActionError-8_0_0_name-8' and @value='errorOdo_maximum']" );
			} );
			// : End
			
			$_actStage = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" )->selected ()->text ();
			$_actOp = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" )->selected ()->text ();
			$_actStatus = $this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->enabled ();
			if ($_actStage == "Pre" && $_actOp == "Update" && $_actStatus) {
				$this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->click ();
			}
			$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
			// : End
			
			// : Update errorOdo_previous refuel update action
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//a[contains(@href,'process?handle=ObjectCrudActionError_update__Process__20050101090000&ObjectRegistry=641&ObjectCrudActionError_id=42&ObjectRegistry_id=403&returnurl=/DataBrowser') and @class='edit' and @title='Update']" );
			} );
			
			$this->_session->element ( "xpath", "//a[contains(@href,'process?handle=ObjectCrudActionError_update__Process__20050101090000&ObjectRegistry=641&ObjectCrudActionError_id=42&ObjectRegistry_id=403&returnurl=/DataBrowser') and @class='edit' and @title='Update']" )->click ();
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//*[contains(text(),'Update Object Crud Action Error')]" );
			} );
			
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" );
			$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" );
			$this->assertElementPresent ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" );
			$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
			
			// : Check if we are updating the correct refuel update action
			$e = $w->until ( function ($session) {
				$this->_session->element ( "xpath", "//input[@id='ObjectCrudActionError-8_0_0_name-8' and @value='errorOdo_previous']" );
			} );
			// : End
			
			$_actStage = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" )->selected ()->text ();
			$_actOp = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" )->selected ()->text ();
			$_actStatus = $this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->enabled ();
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
			throw new Exception ( "Could not continue. Failed to update Refuel Update Actions before starting to run the script." );
		}
		// : End
		
		// : End
		
		// : Main Loop
		foreach ( $this->_data as $truckkey => $value ) {
			foreach ( $value as $datekey => $odovalue ) {
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
				
				// : Run SQL Query to check whether truck exists on MAX
				$_query = preg_replace ( "/%s/", $truckkey, $_queries [1] );
				$_result = $_sqldb->getDataFromQuery ( $_query );
				if ($_result) {
					// : Check if truck is linked to a fleet and if so get the first fleet returned in the query results
					$_truckid = $_result [0] ["id"];
					$_query = preg_replace ( "/%s/", $_truckid, $_queries [0] );
					$_result2 = $_sqldb->getDataFromQuery ( $_query );
					if ($_result2) {
						$_fleetname = $_result2 [0] ["name"];
					}
					// : End
				}
				// : End
				
				if ($_truckid && $_fleetname) {
					try {
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
						
						if ($_fStatus !== "red") {
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[contains(text(),'Initial Refuel Capture')]" );
							} );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-29__0_refuelPoint-29']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-19__0_truck_id-19']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-6__0_driver_id-6']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-8_0_0_fillDateTime-8']" );
							$this->assertElementPresent ( "xpath", "//*[@id='formfield']/textarea" );
							$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
							
							$this->element ( "xpath", "//*[@id='udo_Refuel-29__0_refuelPoint-29']/option[text()='{}']" );
							
							// : Get selected truck if defaulted selected truck is not correct truck
							$_selecttruck = $this->_session->element ( "xpath", "//*[@id='udo_Refuel-19__0_truck_id-19']" )->selected ()->text ();
							if (! $_selecttruck || $_selecttruck !== $truckkey) {
								$this->_session->element ( "xpath", "//*[@id='udo_Refuel-19__0_truck_id-19']/select/option[text()='{$truckkey}']" )->click ();
							}
							// : End
							
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-6__0_driver_id-6']/select/option[text()='']" );
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-8_0_0_fillDateTime-8']" )->clear ();
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-8_0_0_fillDateTime-8']" )->sendKeys ( $datekey );
							$this->_session->element ( "xpath", "//*[@id='formfield']/textarea" )->clear ();
							$this->_session->element ( "xpath", "//*[@id='formfield']/textarea" )->sendKeys ( "This refuel was created by an automated script." );
							$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
							
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
							if ($_fStatus === "red") {
								// : Clear all extra windows and select main window again
								$_winAll = $this->_session->window_handles ();
								// Set window focus to main window
								$this->_session->focusWindow ( $_winAll [0] );
								// If there is more than 1 window open then close all but main window
								if (count ( $_winAll ) > 1) {
									$this->clearWindows ();
								}
								// : End
								
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
						}
						
						if ($_fStatus === "red") {
							$e = $w->until ( function ($session) {
								return $session->element ( "xpath", "//*[contains(text(),'Complete Refuel Capture')]" );
							} );
							// : Confirm refuel order is for the correct truck and order by confirming the details
							try {
								$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-19_0_0_truck_id-19']/tbody/tr/td[text()='{$truckkey}']" );
								$this->assertElementPresent ( "xpath", ".//*[@id='udo_Refuel-8_0_0_fillDateTime-8']/tbody/tr/td[text()='{$datekey}']" );
							} catch ( Exception $e ) {
								throw new Exception ( "Could not confirm that the order been completed was the correct order. Error message: " . $e->getMessage () );
							}
							// : End
							
							// : Check all elements for entering and selecting values to complete refuel are present
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-15_0_0_odo-15']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-12_0_0_litres-12']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-3_0_0_cost-3']" );
							$this->assertElementPresent ( "xpath", "//*[@id='udo_Refuel-9__0_full_or_Partial-9']" );
							$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
							// : End
							
							// Store the refuel order number
							$_refuelOrder = $this->_session->element ( "xpath", "//*[@id='udo_Refuel-18_0_0_refuelOrderNumber_id-18']/tbody/tr/td[1]" )->text ();
							
							// : Enter values into the Complete Refuel form
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-15_0_0_odo-15']" )->clear ();
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-15_0_0_odo-15']" )->sendKeys ( $odovalue );
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-12_0_0_litres-12']" )->clear ();
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-12_0_0_litres-12']" )->sendKeys ();
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-3_0_0_cost-3']" )->clear ();
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-3_0_0_cost-3']" )->sendKeys ();
							$this->_session->element ( "xpath", "//*[@id='udo_Refuel-9__0_full_or_Partial-9']/select/option[text()='Partial']" );
							$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
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
					} catch ( Exception $e ) {
						// : Add details of record when error occured to error array
						$_num = count ( $this->_errors ) + 1;
						$this->_errors [$_num] ["truck"] = $truckkey;
						$this->_errors [$_num] ["date"] = $datekey;
						$this->_errors [$_num] ["odo"] = $odovalue;
						$this->_errors [$_num] ["errormsg"] = $e->getMessage ();
						// : End
					}
				}
			}
			
			// : Turn on refuel update actions
			try {
				$this->_session->open ( $this->_maxurl . self::ADMIN_URL );
				$e = $w->until ( function ($session) {
					return $session->element ( "xpath", "//a[text()='Refuel ']" );
				} );
				
				$this->_session->element ( "xpath", "//a[text()='Refuel ']" )->click ();
				
				$e = $w->until ( function ($session) {
					return $session->element ( "xpath", "//*[@id='toolbar']/div[contains(text(),'Refuel')]" );
				} );
				
				// : Update errorOdo_maximum refuel update action to enable
				$this->assertElementPresent ( "xpath", "//*[@id='subtabselector']/select" );
				$this->_session->element ( "xpath", "//*[@id='subtabselector']/select/option[text()='Update']" )->click ();
				
				$e = $w->until ( function ($session) {
					return $session->element ( "xpath", "//a[contains(@href,'/process?handle=ObjectCrudActionError_update__Process__20050101090000&ObjectRegistry=641&ObjectCrudActionError_id=40&ObjectRegistry_id=403') and @class='edit' and @title='Update']" );
				} );
				
				$this->_session->element ( "xpath", "//a[contains(@href,'/process?handle=ObjectCrudActionError_update__Process__20050101090000&ObjectRegistry=641&ObjectCrudActionError_id=40&ObjectRegistry_id=403') and @class='edit' and @title='Update']" )->click ();
				
				$e = $w->until ( function ($session) {
					return $session->element ( "xpath", "//*[contains(text(),'Update Object Crud Action Error')]" );
				} );
				
				$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
				$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" );
				$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" );
				$this->assertElementPresent ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" );
				$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
				
				// : Check if we are updating the correct refuel update action
				$e = $w->until ( function ($session) {
					$this->_session->element ( "xpath", "//input[@id='ObjectCrudActionError-8_0_0_name-8' and @value='errorOdo_maximum']" );
				} );
				// : End
				
				$_actStage = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" )->selected ()->text ();
				$_actOp = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" )->selected ()->text ();
				$_actStatus = $this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->enabled ();
				if ($_actStage == "Pre" && $_actOp == "Update" && !$_actStatus) {
					$this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->click ();
				}
				$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
				// : End
				
				// : Update errorOdo_previous refuel update action to enable
				$e = $w->until ( function ($session) {
					return $session->element ( "xpath", "//a[contains(@href,'process?handle=ObjectCrudActionError_update__Process__20050101090000&ObjectRegistry=641&ObjectCrudActionError_id=42&ObjectRegistry_id=403&returnurl=/DataBrowser') and @class='edit' and @title='Update']" );
				} );
				
				$this->_session->element ( "xpath", "//a[contains(@href,'process?handle=ObjectCrudActionError_update__Process__20050101090000&ObjectRegistry=641&ObjectCrudActionError_id=42&ObjectRegistry_id=403&returnurl=/DataBrowser') and @class='edit' and @title='Update']" )->click ();
				
				$e = $w->until ( function ($session) {
					return $session->element ( "xpath", "//*[contains(text(),'Update Object Crud Action Error')]" );
				} );
				
				$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-8_0_0_name-8']" );
				$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" );
				$this->assertElementPresent ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" );
				$this->assertElementPresent ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" );
				$this->assertElementPresent ( "css selector", "input[type=submit][name=save]" );
				
				// : Check if we are updating the correct refuel update action
				$e = $w->until ( function ($session) {
					$this->_session->element ( "xpath", "//input[@id='ObjectCrudActionError-8_0_0_name-8' and @value='errorOdo_previous']" );
				} );
				// : End
				
				$_actStage = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-12__0_stage-12']" )->selected ()->text ();
				$_actOp = $this->_session->element ( "xpath", "//*[@id='ObjectCrudActionError-10__0_operation-10']" )->selected ()->text ();
				$_actStatus = $this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->enabled ();
				if ($_actStage == "Pre" && $_actOp == "Update" && !$_actStatus) {
					$this->_session->element ( "xpath", "//*[@id='checkbox_ObjectCrudActionError-4_0_0_enabled-4']" )->click ();
				}
				$this->_session->element ( "css selector", "input[type=submit][name=save]" )->click ();
				// : End
				
			} catch ( Exception $e ) {
				throw new Exception ( "Could not continue. Failed to update Refuel Update Actions after running the script." );
			}
			// : End
			
			// : Report errors if any occured
			if ($this->_errors) {
				$_errfile = dirname ( __FILE__ ) . $this->_datadir . self::DS . $this->getErrorReportFileName () . ".csv";
				$this->ExportToCSV ( $_errfile, $this->_errors );
				echo "Exported error report to the following path and file: " . $_errfile;
			}
			// : End
			
			// : Tear Down
			// Click the logout link
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
	}
	
	// : Private Functions
	
	/**
	 * MAXLive_CreateRefuels::takeScreenshot($_session)
	 * This is a function description for a selenium test function
	 *
	 * @param object: $_session        	
	 */
	private function takeScreenshot($_session) {
		$_img = $_session->screenshot ();
		$_data = base64_decode ( $_img );
		$_file = dirname ( __FILE__ ) . self::DS . date ( "Y-m-d_His" ) . "_WebDriver.png";
		$_success = file_put_contents ( $_file, $_data );
		if ($_success) {
			return $_file;
		} else {
			return FALSE;
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