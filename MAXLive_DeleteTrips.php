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
	const CARGO_URL = "/DataBrowser?browsePrimaryObject=477&browsePrimaryInstance=";
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
	protected $_tmpVar;
	protected $_errors = array ();
	protected $_tmp;
	
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
		if ((array_key_exists ( "datadir", $data ) && $data ["datadir"]) && (array_key_exists ( "screenshotdir", $data ) && $data ["screenshotdir"]) && (array_key_exists ( "errordir", $data ) && $data ["errordir"]) && (array_key_exists ( "file1", $data ) && $data ["file1"]) && (array_key_exists ( "username", $data ) && $data ["username"]) && (array_key_exists ( "password", $data ) && $data ["password"]) && (array_key_exists ( "welcome", $data ) && $data ["welcome"]) && (array_key_exists ( "mode", $data ) && $data ["mode"]) && (array_key_exists ( "wdport", $data ) && $data ["wdport"]) && (array_key_exists ( "proxy", $data ) && $data ["proxy"]) && (array_key_exists ( "browser", $data ) && $data ["browser"])) {
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
	public function testDeleteTrips() {
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
				"select id, tripNumber from udo_cargo where id=%d;" 
		);
		// : End
		
		// Build file path string
		$_file1 = dirname ( __FILE__ ) . self::DS . $this->_datadir . self::DS . $this->_file1;
		
		// : Import CSV files data
		if (file_exists ( $_file1 )) {
			$_csvfile = new FileParser ( $_file1 );
			$_tripsData = $_csvfile->parseFile ();
			unset ( $_csvfile );
		}
		
		try {
			// Initialize session
			$session = $this->_session;
			$this->_session->setPageLoadTimeout ( 60 );
			$w = new PHPWebDriver_WebDriverWait ( $session, 30 );
			
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
		
		// : Main Loop
		$_count = 0;
		
		if ($_tripsData && is_array($_tripsData)) {
		    
		    foreach($_tripsData as $key => $value) {
		        try {
		        $this->_session->open( $this->_maxurl . self::CARGO_URL . $value["MAX ID"]);
		        
		        $this->_tmp = $value["Trip Number"];
		        $e = $w->until ( function ($session) {
		            return $session->element ( "xpath", "//div[@class='detail-title' and contains(text(),'{$this->_tmp}')]" );
		        } );
		        
		        $this->assertElementPresent ( "css selector", "div.toolbar-cell-update" );
		        $this->_session->element("css selector", "div.toolbar-cell-update")->click();

		        $e = $w->until ( function ($session) {
		            return $session->element ( "xpath", "//*[contains(text(), 'Update Cargo Details')]" );
		        } );
		        
		        $this->_tmp = $this->_tmp . strval($_count);
		        
		        $this->assertElementPresent ( "xpath", "//*[@name='udo_Cargo[0][tripNumber]']" );
		        $this->assertElementPresent ( "xpath", "//*[@name='udo_TripLeg[2][loadingArrivalETA]']" );
		        $this->assertElementPresent ( "xpath", "//*[@name='udo_TripLeg[2][offloadingArrivalETA]']" );
		        $this->assertElementPresent ( "xpath", "//*[@name='udo_TripLeg[2][loadingArrivalTime]']" );
		        $this->assertElementPresent ( "xpath", "//*[@name='udo_TripLeg[2][loadingStarted]']" );
		        $this->assertElementPresent ( "xpath", "//*[@name='udo_TripLeg[2][loadingFinished]']" );
		        $this->assertElementPresent ( "xpath", "//*[@name='udo_TripLeg[2][timeLeft]']" );
		        $this->assertElementPresent ( "xpath", "//*[@name='udo_TripLeg[2][offloadingArrivalTime]']" );
		        $this->assertElementPresent ( "xpath", "//*[@name='udo_TripLeg[2][offloadingStarted]']" );
		        $this->assertElementPresent ( "xpath", "//*[@name='udo_TripLeg[2][offloadingCompleted]']" );
		        $this->assertElementPresent ( "css selector", "input[name=save][type=submit]" );
		        
		        $this->_session->element ( "xpath", "//*[@name='udo_Cargo[0][tripNumber]']" )->clear();
		        $this->_session->element ( "xpath", "//*[@name='udo_Cargo[0][tripNumber]']" )->sendKeys($this->_tmp);
		        
		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][loadingArrivalETA]']" )->clear();
		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][offloadingArrivalETA]']" )->clear();

		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][loadingArrivalETA]']" )->sendKeys(date("Y-m-d H:i:s", strtotime("+1 year")));
		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][offloadingArrivalETA]']" )->sendKeys(date("Y-m-d H:i:s", strtotime("+1 year +1 hour")));
		        
		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][loadingArrivalTime]']" )->clear();
		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][loadingStarted]']" )->clear();
		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][loadingFinished]']" )->clear();
		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][timeLeft]']" )->clear();
		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][offloadingArrivalTime]']" )->clear();
		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][offloadingStarted]']" )->clear();
		        $this->_session->element ( "xpath", "//*[@name='udo_TripLeg[2][offloadingCompleted]']" )->clear();
		        
		        $this->_session->element ( "css selector", "input[name=save][type=submit]" )->click();
		        
		        $e = $w->until ( function ($session) {
		            return $session->element ( "xpath", "//div[@class='detail-title' and contains(text(),'{$this->_tmp}')]" );
		        } );
		        
		        $this->assertElementPresent ( "css selector", "div.toolbar-cell-delete" );
		        $this->_session->element("css selector", "div.toolbar-cell-delete")->click();
		        
		        $e = $w->until ( function ($session) {
		            return $session->element ( "xpath", "//*[contains(text(), 'Driver')]" );
		        } );
		        
		        $e = $w->until ( function ($session) {
		          return $session->element ( "xpath", "//*[contains(text(), 'Cargo')]" );
		        } );
		        
		        $this->assertElementPresent ( "css selector", "input[name=delete][type=submit]" );
		        $this->_session->element ( "css selector", "input[name=delete][type=submit]" )->click();
		        
		        $e = $w->until ( function ($session) {
		            return $session->element ( "xpath", "//div[@id='active_process' and contains(text(), 'Busy processing')]" );
		        } );
		        
		        $e = $w->until ( function ($session) {
		          return $session->element ( "xpath", "//*[contains(text(), 'Delete Cargo')]" );
		        } );
		        
		        $e = $w->until ( function ($session) {
		          return $session->element ( "xpath", "//*[not(contains(text(), 'Driver'))]" );
		        } );
		        
		            $e = $w->until ( function ($session) {
		                return $session->element ( "css selector", "input[name=delete][type=submit]" );
		            } );
		        
		        $this->assertElementPresent ( "css selector", "input[name=delete][type=submit]" );
		        $this->_session->element ( "css selector", "input[name=delete][type=submit]" )->click();
		        
		        } catch (Exception $e) {
		            $this->_errors[] = "Failed to delete a cargo: " . $value["MAX ID"] . " - " . $e->getMessage();
		        }
		        
		        $_count++;
		    }  
		}
		
		// : End
		
		// : Report errors if any occured
		if ($this->_errors) {
		    var_dump($this->_errors);
			$_errfile = dirname ( __FILE__ ) . $this->_errdir . self::DS . "error_report_" . $this->getReportFileName () . ".csv";
			$this->exportToCSV ( $_errfile, $this->_errors );
			echo "Exported error report to the following path and file: " . $_errfile;
		}
		// : End
		
		// : Report all successful completed refuel orders
		$_orders = ( array ) array ();
		foreach ( $this->_data as $key => $value ) {
			$_orders [$key] ["id"] = $key;
			if (array_key_exists ( "OrderNumber", $value )) {
				$_orders [$key] = $value;
			}
		}
		
		if ($_orders) {
			$_ordersfile = dirname ( __FILE__ ) . "/export" . self::DS . $this->getReportFileName () . ".csv";
			$this->exportToCSV ( $_ordersfile, $_orders );
			echo "Exported successfully created refuels report to the following path and file: " . $_ordersfile;
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
	private function takeScreenshot($_session, $_filename) {
		$_img = $_session->screenshot ();
		$_data = base64_decode ( $_img );
		$_file = dirname ( __FILE__ ) . $this->_scrdir . self::DS . date ( "Y-m-d_His" ) . $_filename;
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
	 * MAXLive_CreateRefuels::exportToCSV($csvFile, $arr)
	 * From supplied csv file save data into multidimensional array
	 *
	 * @param string: $csvFile        	
	 * @param array: $_arr        	
	 */
	private function exportToCSV($csvFile, $_arr) {
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