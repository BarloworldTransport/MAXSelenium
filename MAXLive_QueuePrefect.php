<?php
// Set error reporting level for this script
error_reporting ( E_ALL );

// : Includes

require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';

// : End

/**
 * MAXLive_QueuePrefect.php
 *
 * @package MAXLive_QueuePrefect
 * @author Clinton Wright <cwright@bwtrans.co.za>
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
class MAXLive_QueuePrefect extends PHPUnit_Framework_TestCase {
	// : Constants
	const DS = DIRECTORY_SEPARATOR;
	const LIVE_URL = "https://login.max.bwtsgroup.com";
	const TEST_URL = "http://max.mobilize.biz";
	const INI_FILE = "user_data.ini";
	const PB_URL = "/Planningboard";
	const QUEUES_URL = "/adminTop/queue?&tab_id=180";
	const FILE_NOT_FOUND = "ERROR: File not found. Please check the path and that the file exists and try again: %s";
	const LOGIN_FAIL = "ERROR: Log into %h was unsuccessful. Please see the following error message relating to the problem: %s";
	const DB_ERROR = "ERROR: There was a problem connecting to the database. See error message: %s";
	const DIR_NOT_FOUND = "The specified directory was not found: %s";
	const ADMIN_URL = "/adminTop?&tab_id=120";
	const NOT_CORRECT_ACTION = "Could not verify the action was correct when updating the action update for: %s";
	const DROPS_IGNORE = 3;
	const RESET_LOOP_COUNT = 10;
	
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
	 * MAXLive_QueuePrefect::getReportFileName()
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
	 * MAXLive_QueuePrefect::__construct()
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
	 * MAXLive_QueuePrefect::__destruct()
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
	 * MAXLive_QueuePrefect::testFunctionTemplate
	 * This is a function description for a selenium test function
	 */
	public function testQueuePrefect() {
		// Create new object for included class to import data
		
		try {
			// Initialize session
			$session = $this->_session;
			$this->_session->setPageLoadTimeout ( 60 );
			// Make change to WebDriverWait.php file to _construct public function arg $timeout. Make required for passing the argument
			$w = new PHPWebDriver_WebDriverWait ( $session, 10 );
			
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
		}
		
		// : Main Endless Loop
		$_data = array ();
		$_scriptrun = TRUE;
		$_loopcount = 0;
		
		$this->_session->open ( $this->_maxurl . self::QUEUES_URL );
		
		$e = $w->until ( function ($session) {
			return $session->element ( "xpath", "//a[contains(text(),'Process')]" );
		} );
		
		// : Get number of rows and columns for queue table
		$_rows = count ( $this->_session->elements ( "xpath", "//*[@id='itemlist']/table/tbody/tr" ) );
		$_cols = count ( $this->_session->elements ( "xpath", "//*[@id='itemlist']/table/tbody/tr[3]/td" ) );
		// : End
		
		// : Loop through cells of table to get queue data
		for($x = 3; $x <= $_rows; $x ++) {
			// Get queue name
			$queuename = $this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[2]/nobr/a" )->text ();
			for($y = 1; $y <= $_cols; $y ++) {
				$_data [$queuename] ["rownum"] = $x;
				$_data [$queuename] ["colnum"] = $y;
				$_data [$queuename] ["drops"] = 0;
				switch ($y) {
					case 3 :
						// Get status of queue -> used to determine whether to bring up if down
						$_status = $this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[$y]" )->text ();
						$_data [$queuename] ["status"] = preg_replace ( "/\s/", "", $_status );
						break;
					case 4 :
						// Get amount of ready entries for queue
						$_data [$queuename] ["ready"] = $this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[$y]" )->text ();
						break;
					case 5 :
						// Get amount of manual entries for queue
						$_data [$queuename] ["manual"] = $this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[$y]" )->text ();
						break;
					case 6 :
						// Get amount of cancelled entries for queue
						$_data [$queuename] ["cancel"] = $this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[$y]" )->text ();
						break;
					default :
						break;
				}
				$this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[$y]" );
			}
		}
		// : End
		
		// : Main Endless Run
		while ( $_scriptrun ) {
			try {
			$this->_session->open ( $this->_maxurl . self::QUEUES_URL );
			
			$e = $w->until ( function ($session) {
				return $session->element ( "xpath", "//a[contains(text(),'Process')]" );
			} );
			
			// : Loop through cells of table to get queue data
			for($x = 3; $x <= $_rows; $x ++) {
				// Loop through cells in row
				$queuename = $this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[2]/nobr/a" )->text ();
				for($y = 1; $y <= $_cols; $y ++) {
					switch ($y) {
						case 3 :
							// Get status of queue -> used to determine whether to bring up if down
							$_status = $this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[$y]" )->text ();
							$_data [$queuename] ["status"] = preg_replace ( "/\s/", "", $_status );
						case 4 :
							// Get amount of ready entries for queue
							$_data [$queuename] ["ready"] = $this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[$y]" )->text ();
							break;
						case 5 :
							// Get amount of manual entries for queue
							$_data [$queuename] ["manual"] = $this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[$y]" )->text ();
							break;
						case 6 :
							// Get amount of cancelled entries for queue
							$_data [$queuename] ["cancel"] = $this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[$x]/td[$y]" )->text ();
							break;
						default :
							break;
					}
				}
			}
			// : End
			
			
			// : Loop through fetched queue data to check if any queues are down and bring up
			foreach ( $_data as $_queuename => $_values ) {
				if ($_values ["status"] == "Stop" && $_values["drops"] < self::DROPS_IGNORE) {
					$this->_session->element ( "xpath", "//*[@id='itemlist']/table/tbody/tr[{$_values["rownum"]}]/td[1]/nobr/form/a" )->click ();
					
					$this->_tmp = $_queuename;
					$e = $w->until ( function ($session) {
						return $session->element ( "xpath", "//*[text()='{$this->_tmp}']" );
					} );
					
					$e = $w->until ( function ($session) {
						return $session->element ( "xpath", "//*[@id='Queue-2_0_0_status-2[Ready]']" );
					} );
					
					$_radioStatus = $this->_session->element ( "xpath", "//*[@id='Queue-2_0_0_status-2[Ready]']" )->attribute("checked");
					
					if (!$_radioStatus) {
						$this->_session->element ( "xpath", "//*[@id='Queue-2_0_0_status-2[Ready]']" )->click();
					}
					
					$this->_session->element("css selector", "input[type=submit][name=save]")->click();
					
					$e = $w->until ( function ($session) {
						return $session->element ( "xpath", "//a[contains(text(),'Process')]" );
					} );
					$_data[$_queuename]["drops"]++;
				}
			}
			// : End
			
			// Wait for 60 seconds before proceeding
			sleep ( 60 );
			
			// : Increment loop count or reset it
			if ($_loopcount < self::RESET_LOOP_COUNT) {
				
				$_loopcount++;
				
			} else if ($_loopcount === self::RESET_LOOP_COUNT) {
				
				// Reset loopcount
				$_loopcount = 0;
				
				// : Reset queue drop count for each queue
				foreach($_data as $key => $value) {
					$_data[$key]["drops"] = 0;
				}
				// : End
				
			}
			// : End
			} catch (Exception $e) {
				$this->_session->open ( $this->_maxurl . self::QUEUES_URL );
					
				$e = $w->until ( function ($session) {
					return $session->element ( "xpath", "//a[contains(text(),'Process')]" );
				} );
			}
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
	 * MAXLive_QueuePrefect::takeScreenshot($_session)
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
	 * MAXLive_QueuePrefect::assertElementPresent($_using, $_value)
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
	 * MAXLive_QueuePrefect::getSelectedOptionValue($_using, $_value)
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
	 * MAXLive_QueuePrefect::clearWindows()
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
	 * MAXLive_QueuePrefect::ExportToCSV($csvFile, $arr)
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