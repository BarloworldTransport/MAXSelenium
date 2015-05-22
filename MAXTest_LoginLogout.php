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
require_once 'automationLibrary.php';
require_once 'MAX_LoginLogout.php';
require_once "MAX_API_Get.php";

// : End

/**
 * MAXTest_LoginLogout.php
 *
 * @package MAXTest_LoginLogout
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
class MAXTest_LoginLogout extends PHPUnit_Framework_TestCase {
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
	protected $_data = array ();
	protected $_datadir;
	protected $_errdir;
	protected $_scrdir;
	protected $_errors = array ();
	protected $_tmp;
	protected $_apiuserpwd;
	protected $_version;
	
	// : Public Functions
	// : Accessors
	// : End
	
	// : Magic
	/**
	 * MAXTest_LoginLogout::__construct()
	 * Class constructor
	 */
	public function __construct() {
		$ini = dirname ( realpath ( __FILE__ ) ) . self::DS . "ini" . self::DS . self::INI_FILE;
		
		if (is_file ( $ini ) === FALSE) {
			echo "No " . self::INI_FILE . " file found. Please create it and populate it with the following data: username=x@y.com, password=`your password`, your name shown on MAX the welcome page welcome=`Joe Soap` and mode=`test` or `live`" . PHP_EOL;
			return FALSE;
		}
		$data = parse_ini_file ( $ini );
		if ((array_key_exists ( "datadir", $data ) && $data ["datadir"]) && (array_key_exists ( "screenshotdir", $data ) && $data ["screenshotdir"]) && (array_key_exists ( "errordir", $data ) && $data ["errordir"]) && (array_key_exists ( "username", $data ) && $data ["username"]) && (array_key_exists ( "password", $data ) && $data ["password"]) && (array_key_exists ( "welcome", $data ) && $data ["welcome"]) && (array_key_exists ( "mode", $data ) && $data ["mode"]) && (array_key_exists ( "wdport", $data ) && $data ["wdport"]) && (array_key_exists ( "proxy", $data ) && $data ["proxy"]) && (array_key_exists ( "browser", $data ) && $data ["browser"])) {
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
			$this->_version = $data ["version"];
			$this->_apiuserpwd = $data ["apiuserpwd"];
				
			// Determine MAX URL to be used for this test run
			$this->_maxurl = automationLibrary::getMAXURL($this->_mode, $this->_version);
		} else {
			echo "The correct data is not present in user_data.ini. Please confirm. Fields are username, password, welcome and mode" . PHP_EOL;
			return FALSE;
		}
	}
	
	/**
	 * MAXTest_LoginLogout::__destruct()
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
	 * MAXTest_LoginLogout::testFunctionTemplate
	 * This is a function description for a selenium test function
	 */
	public function testLoginLogout() {
		try {
			// Initialize session
			$session = $this->_session;
			$this->_session->setPageLoadTimeout ( 60 );
			$w = new PHPWebDriver_WebDriverWait ( $session, 10 );
			
			$_autoLib = new automationLibrary($this->_session, $this, $w, $this->_mode, $this->_version);
			$_maxLoginLogout = new maxLoginLogout($_autoLib, $this->_maxurl);
				
			// Log into MAX
			if (!$_maxLoginLogout->maxLogin($this->_username, $this->_password, $this->_welcome, $this->_version)) {
			    throw new Exception($_maxLoginLogout->getLastError());
			}
			
			if (!$_maxLoginLogout->maxLogout($this->_version)) {
			    throw new Exception($_maxLoginLogout->getLastError());
			}
		} catch (Exception $e) {
		    print($e->getMessage());
		}
		// Terminate session
		$this->_session->close ();
		// : End
	}
	
	// : Private Functions
	
	/**
	 * MAXTest_LoginLogout::takeScreenshot($_session)
	 * This is a function description for a selenium test function
	 *
	 * @param object: $_session        	
	 */
	private function takeScreenshot($_session, $_filename) {
		$_img = $_session->screenshot ();
		$_data = base64_decode ( $_img );
		$_file = dirname ( __FILE__ ) . $this->_scrdir . self::DS . date ( "Y-m-d_His" ) . $_filename . "png";
		$_success = file_put_contents ( $_file, $_data );
		if ($_success) {
			return $_file;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * MAXTest_LoginLogout::assertElementPresent($_using, $_value)
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
	 * MAXTest_LoginLogout::getSelectedOptionValue($_using, $_value)
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
	// : End
}