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
				"select f.name from udo_fleetrucklink as ftl left join udo_truck as t on (t.id=ftl.truck_id) left join udo_fleet as f on (f.id=ftl.fleet_id) where t.fleetnum='%s';",
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
		} catch ( Exception $e ) {
			$_errmsg = preg_replace ( "/%h/", $this->_maxurl, self::LOGIN_FAIL );
			$_errmsg = preg_replace ( "/%h/", $e->getMessage (), $_errmsg );
			throw new Exception ( $_errmsg );
			unset ( $_errmsg );
		}
		
		// : End
		
		// : Main Loop
		foreach ( $this->_data as $truckkey => $value ) {
			foreach ( $value as $datekey => $odovalue ) {
				$_truckid = "";
				$_fleetname = "";
				$_query = preg_replace ( "/%s/", $truckkey, $_queries [1] );
				$_result = $_sqldb->getDataFromQuery ( $_query );
				if ($_result) {
					$_query = preg_replace ( "/%s/", $truckkey, $_queries [0] );
					$_result2 = $_sqldb->getDataFromQuery ( $_query );
					if ($_result2) {
						$_truckid = $_result [0] ["id"];
						$_fleetname = $_result2 [0] ["name"];
					}
				}
				
				if ($_truckid && $_fleetname) {
					try {
					$this->_tmp = $_fleetname;
					// Load the fleet to which the truck is linked too
					$e = $w->until ( function ($session) {
						return $session->element ( "xpath", "//*[@id='fplanningboard']/table/tbody/tr[2]/td[1]/select/option[contains(text(),'{$this->_tmp}')]" );
					} );
					
					$this->_session->element ( "xpath", "//*[@id='fplanningboard']/table/tbody/tr[2]/td[1]/select/option[contains(text(),'{$_fleetname}')]" )->click ();
					
					$this->_tmp = $truckkey;
					$e = $w->until ( function ($session) {
						return $session->element ( "xpath", "//*[@id='planningBoardLabels']/table/tr[2]/td[1][contains(text(),'{$this->_tmp}')]" );
					} );
					
					
					} catch (Exception $e) {
						
					}
			}
		}
		
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