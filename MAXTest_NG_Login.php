<?php
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
include_once dirname(__FILE__) . '/RatesReadXLSData.php';
include_once 'PHPUnit/Extensions/PHPExcel/Classes/PHPExcel.php';

/**
 * Object::MAXTest_NG_Login
 *
 * @author Clinton Wright
 * @author cwright@bwtsgroup.com
 * @copyright 2011 onwards Barloworld Transport Solutions (Pty) Ltd
 * @license GNU GPL
 * @see http://www.gnu.org/copyleft/gpl.html
 */
class MAXTest_NG_Login extends PHPUnit_Framework_TestCase
{
    // : Constants
    const COULD_NOT_CONNECT_MYSQL = "Failed to connect to MySQL database";

    const MAX_NOT_RESPONDING = "Error: MAX does not seem to be responding";

    const DS = DIRECTORY_SEPARATOR;

    const LIVE_URL = "http://login.max3.bwtsgroup.com";

    const TEST_URL = "http://max3.mobilize.biz";

    const INI_FILE = "user_data.ini";

    const INI_DIR = "ini";

    const XLS_CREATOR = "MAXTest_NG_Login.php";

    const XLS_TITLE = "Error Report";

    const XLS_SUBJECT = "Errors caught while creating rates for subcontracts";
    
    // : Variables
    protected static $driver;

    protected $_dummy;

    protected $_session;

    protected $lastRecord;

    protected $to = 'clintonabco@gmail.com';

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

    protected $_xls;

    protected $_maxurl;

    protected $_error = array();

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
    // : Accessors
    
    // : End
    
    // : Magic
    /**
     * MAXTest_NG_Login::__construct()
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
        if ((array_key_exists("browser", $data) && $data["browser"]) && (array_key_exists("wdport", $data) && $data["wdport"]) && (array_key_exists("errordir", $data) && $data["errordir"]) && (array_key_exists("screenshotdir", $data) && $data["screenshotdir"]) && (array_key_exists("datadir", $data) && $data["datadir"]) && (array_key_exists("ip", $data) && $data["ip"]) && (array_key_exists("username", $data) && $data["username"]) && (array_key_exists("password", $data) && $data["password"]) && (array_key_exists("welcome", $data) && $data["welcome"]) && (array_key_exists("mode", $data) && $data["mode"])) {
            $this->_username = $data["username"];
            $this->_password = $data["password"];
            $this->_welcome = $data["welcome"];
            $this->_dataDir = $data["datadir"];
            $this->_errDir = $data["errordir"];
            $this->_scrDir = $data["screenshotdir"];
            $this->_mode = $data["mode"];
            $this->_ip = $data["ip"];
            $this->_wdport = $data["wdport"];
            $this->_browser = $data["browser"];
            $this->_xls = $data["xls"];
            switch ($this->_mode) {
                case "live":
                    $this->_maxurl = self::LIVE_URL;
                    break;
                default:
                    $this->_maxurl = self::TEST_URL;
            }
        } else {
            echo "The correct data is not present in " . self::INI_FILE . ". Please confirm. Fields are username, password, welcome and mode" . PHP_EOL;
            return FALSE;
        }
    }

    /**
     * MAXTest_NG_Login::__destruct()
     * Class destructor
     * Allow for garbage collection
     */
    public function __destruct()
    {
        unset($this);
    }
    // : End
    
    /**
     * MAXTest_NG_Login::setUp()
     * Create new class object and initialize session for webdriver
     */
    public function setUp()
    {
        $wd_host = "http://localhost:$this->_wdport/wd/hub";
        self::$driver = new PHPWebDriver_WebDriver($wd_host);
        // : Setup WebDriverProxy object to include in session parameters
        $desired_capabilities = array();
        $proxy = new PHPWebDriver_WebDriverProxy();
        $proxy->httpProxy = $this->_proxyip;
        $proxy->add_to_capabilities($desired_capabilities);
        // : End
        // Initialize webdriver session with proxy capabilities
        $this->_session = self::$driver->session($this->_browser, $desired_capabilities);
    }

    /**
     * MAXTest_NG_Login::testCreateContracts()
     * Pull F and V Contract data and automate creation of F and V Contracts
     */
    public function testCreateContracts()
    {
        
        // : Login
        try {
            // Initiate Session
            $session = $this->_session;
            $this->_session->setPageLoadTimeout(90);
            // Create a reference to the session object for use with waiting for elements to be present
            $w = new PHPWebDriver_WebDriverWait($this->_session);
            // : End
            // Load MAX home page
            $this->_session->open($this->_maxurl);
            // : Wait for page to load and for elements to be present on page
            $e = $w->until(function ($session) {
                return $session->element('css selector', "#identification");
            });
            // : End
            $this->assertElementPresent('css selector', '#identification');
            $this->assertElementPresent('css selector', '#password');
            $this->assertElementPresent('css selector', '#btn_Sign_In');
            $this->_session->element('css selector', '#identification')->sendKeys($this->_username);
            $this->_session->element('css selector', '#password')->sendKeys($this->_password);
            ;
            $this->_session->element('css selector', '#btn_Sign_In')->click();
        } catch (Exception $e) {
            throw new Exception("Error: Failed to log into MAX." . PHP_EOL . $e->getMessage());
        }
        // : End
        
        // : Main Loop
        // : End
    }
    
    // : Private Functions
    
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
     * MAXTest_NG_Login::assertElementPresent($_using, $_value)
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
     * MAXTest_NG_Login::selectWindow($_title)
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
     * MAXTest_NG_Login::clearWindows()
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
    
    // : End
}