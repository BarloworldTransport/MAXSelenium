<?php
require_once ('PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php');
require_once ('PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php');
require_once ('PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php');

/**
 * Object::web_driver_template_for_max_tests
 *
 * @author Clinton Wright
 * @author cwright@bwtsgroup.com
 * @copyright 2011 onwards Manline Group (Pty) Ltd
 * @license GNU GPL
 * @see http://www.gnu.org/copyleft/gpl.html
 */
class web_driver_template_for_max_tests extends PHPUnit_Framework_TestCase {
	// : Constants
	const DS = DIRECTORY_SEPARATOR;
	const LIVE_URL = "https://login.max.bwtsgroup.com";
	const TEST_URL = "http://max.mobilize.biz";
	const INI_FILE = "user_data.ini";
	const TEST_SESSION = "firefox";
	
	// : Variables
	protected static $driver;
	protected $_maxurl;
	protected $_mode;
	protected $_username;
	protected $_password;
	protected $_welcome;
	
	// : Public Functions
	// : Accessors
	// : End
	
	// : Magic
	/**
	 * web_driver_template_for_max_tests::__construct()
	 * Class constructor
	 */
	public function __construct() {
		$ini = dirname ( realpath ( __FILE__ ) ) . self::DS . "ini" . self::DS . "user_data.ini";
		echo $ini;
		if (is_file ( $ini ) === FALSE) {
			echo "No " . self::INI_FILE . " file found. Please create it and populate it with the following data: username=x@y.com, password=`your password`, your name shown on MAX the welcome page welcome=`Joe Soap` and mode=`test` or `live`" . PHP_EOL;
			return FALSE;
		}
		$data = parse_ini_file ( $ini );
		if ((array_key_exists ( "username", $data ) && $data ["username"]) && (array_key_exists ( "password", $data ) && $data ["password"]) && (array_key_exists ( "welcome", $data ) && $data ["welcome"]) && (array_key_exists ( "mode", $data ) && $data ["mode"])) {
			$this->_username = $data ["username"];
			$this->_password = $data ["password"];
			$this->_welcome = $data ["welcome"];
			$this->_mode = $data ["mode"];
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
	 * web_driver_template_for_max_tests::__destruct()
	 * Class destructor
	 * Allow for garbage collection
	 */
	public function __destruct() {
		unset ( $this );
	}
	// : End
	
	public function setUp() {
		$wd_host = 'http://localhost:4445/wd/hub';
		self::$driver = new PHPWebDriver_WebDriver ($wd_host);
		$this->_session = self::$driver->session ( self::TEST_SESSION );
	}
	
	/**
	 * web_driver_template_for_max_tests::testFunctionTemplate
	 * This is a function description for a selenium test function
	 */
	public function testFunctionTemplate() {
		$session = $this->_session;
		$this->_session->setPageLoadTimeout ( 60 );
		$w = new PHPWebDriver_WebDriverWait ( $session );
		$this->_session->open ( "file:///home/clinton/workspace/php_workspace/index.html" );
		
		// : Wait for page to load and for elements to be present on page
		$e = $w->until ( function ($session) {
			return $session->element ( 'css selector', "#customer_name" );
		} );
		
		$this->assertElementPresent("link text","Create New Location");
		$this->_session->element("link text","Create New Location")->click();
		
		$_winAll = $this->_session->window_handles();
		
		if (count($_winAll) > 1) {
			$this->_session->focusWindow($_winAll[1]);
		}
		
		// : Wait for page to load and for elements to be present on page
		$e = $w->until ( function ($session) {
			return $session->element ( "css selector", "#location_name" );
		} );
		
		$this->assertElementPresent("css selector", "#location_name");
		$this->assertElementPresent("css selector", "#parent_location");
		$this->assertElementPresent("css selector", "#savebtn");
		
		$this->_session->element("css selector", "#location_name")->sendKeys("New Town Location Test");
		$this->_session->element("xpath", "//*[@id='parent_location']/option[text()='Durban']")->click();
		
		if (count($_winAll) > 1) {
			$this->_session->focusWindow($_winAll[0]);
		}		
		
				// : Wait for page to load and for elements to be present on page
		$e = $w->until ( function ($session) {
			return $session->element ( "css selector", "#customer_name" );
		} );
		
		$this->_session->element ( "xpath", "//*[@id='customer_name']/option[text()='NCP']" )->click();
		
		$_winAll = $this->_session->window_handles();
		$_curWin = $this->_session->window_handle();
		foreach($_winAll as $_win) {
			if ($_win != $_curWin) {
				$this->_session->focusWindow($_win);
				$this->_session->deleteWindow();
			}
		}
		$this->_session->focusWindow($_curWin);
		
		$e = $w->until ( function ($session) {
			return $session->element ( "css selector", "#customer_name" );
		} );
		
		$this->clearWindows();
		
		$this->_session->close ();
		// : End
	}
	
	// : Private Functions
	
	/**
	 * web_driver_template_for_max_tests::takeScreenshot($_session)
	 * This is a function description for a selenium test function
	 *
	 * @param object: $_session        	
	 */
	private function takeScreenshot($_session) {
		$_img = $_session->screenshot ();
		$_data = base64_decode ( $_img );
		$_file = dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . "Screenshots" . DIRECTORY_SEPARATOR . date ( "Y-m-d_His" ) . "_WebDriver.png";
		$_success = file_put_contents ( $_file, $_data );
		if ($_success) {
			return $_file;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * MAXLive_NCP_Rates_Create::assertElementPresent($_using, $_value)
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
	 * MAXLive_NCP_Rates_Create::assertElementPresent($_title)
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
			$_foundWin = "";
			$_results = ( array ) array ();
			// Store the current window handle value
			$_currentWin = $this->_session->window_handle ();
			// Get all open windows handles
			$_winAll = $this->_session->window_handles ();
			if (count ( $_winAll ) > 1) {
				foreach ( $_winAll as $_browserWindow ) {
					$this->_session->focusWindow ( $_browserWindow );
					$_page_title = $this->_session->title ();
					preg_match ( "/^.+" . $_title . ".+/", $_page_title, $_results );
					print_r($_results);
					if ((count ( $_results ) != 0) && ($_browserWindow != $_currentWin)) {
						$_foundWin = $_browserWindow;
					}
				}
			}
			if ($_foundWin != "") {
				$this->_session->focusWindow ( $_foundWin );
				return true;
			} else {
				return false;
			}
		} catch ( Exception $e ) {
			return false;
		}
	}
	
	/**
	 * MAXLive_NCP_Rates_Create::clearWindows()
	 * This functions switches focus between each of the open windows
	 * and looks for the first window where the page title matches
	 * the given title and returns true else false
	 *
	 * @param object: $this->_session
	 */
	private function clearWindows() {
		$_winAll = $this->_session->window_handles();
		$_curWin = $this->_session->window_handle();
		foreach($_winAll as $_win) {
			if ($_win != $_curWin) {
				$this->_session->focusWindow($_win);
				$this->_session->deleteWindow();
			}
		}
		$this->_session->focusWindow($_curWin);
	}
	
	// : End
	
}

?>