<?php

// : Error reporting settings
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', dirname(__FILE__) . '/my-errors.log');
ini_set('display_errors', '0');
// : End

// : Includes
include_once ('PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php');
include_once ('PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php');
include_once ('PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php');
include_once ('PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php');
include_once dirname(__FILE__) . '/FandVReadXLSData.php';
include_once 'PHPUnit/Extensions/PHPExcel/Classes/PHPExcel.php';
/**
 * PHPExcel_Writer_Excel2007
 */
include 'PHPUnit/Extensions/PHPExcel/Classes/PHPExcel/Writer/Excel2007.php';
// : End

/**
 * Object::MAXLive_CreateFandVContracts
 *
 * @author Clinton Wright
 * @author cwright@bwtsgroup.com
 * @copyright 2011 onwards Manline Group (Pty) Ltd
 * @license GNU GPL
 * @see http://www.gnu.org/copyleft/gpl.html
 */
class MAXLive_CreateFandVContracts extends PHPUnit_Framework_TestCase
{
    // : Constants
    const PB_URL = "/Planningboard";

    const COULD_NOT_CONNECT_MYSQL = "Failed to connect to MySQL database";

    const MAX_NOT_RESPONDING = "Error: MAX does not seem to be responding";

    const CUSTOMER_URL = "/DataBrowser?browsePrimaryObject=461&browsePrimaryInstance=";

    const LOCATION_BU_URL = "/DataBrowser?browsePrimaryObject=495&browsePrimaryInstance=";

    const OFF_CUST_BU_URL = "/DataBrowser?browsePrimaryObject=494&browsePrimaryInstance=";

    const RATEVAL_URL = "/DataBrowser?browsePrimaryObject=udo_Rates&browsePrimaryInstance=%s&browseSecondaryObject=DateRangeValue&relationshipType=Rate";

    const CONTRIB = "Freight (Long Distance)";

    const LIVE_URL = "https://login.max.bwtsgroup.com";

    const TEST_URL = "http://max.mobilize.biz";

    const INI_FILE = "fandv_data.ini";

    const INI_DIR = "ini";

    const TEST_SESSION = "firefox";

    const CUSTOMERURL = "/DataBrowser?browsePrimaryObject=461&browsePrimaryInstance=%s&browseSecondaryObject=910&useDataViewForSecondary=758&tab_id=61";

    const FANDVURL = "/DataBrowser?browsePrimaryObject=910&browsePrimaryInstance=";

    const RATEDATAURL = "/DataBrowser?browsePrimaryObject=udo_Rates&browsePrimaryInstance=";

    const DS = DIRECTORY_SEPARATOR;

    const XLS_CREATOR = "MAXLive_CreateFandVContracts.php";

    const XLS_TITLE = "Error Report";

    const XLS_SUBJECT = "Errors caught while creating F & V contracts";
    
    // : Variables
    protected static $driver;

    protected $_dummy;

    protected $_session;

    protected $lastRecord;

    protected $to = 'clintonabco@gmail.com';

    protected $subject = 'MAX Selenium script report';

    protected $message;

    protected $_maxurl;

    protected $_mode;

    protected $_error = array();

    protected $_wdport;

    protected $var_rate_id;

    protected $_username;

    protected $_password;

    protected $_welcome;

    protected $_xls;

    protected $_ip;

    protected $_proxyip;

    protected $_data;

    protected $_dataDir;

    protected $_errDir;

    protected $_scrDir;

    protected $_db;

    protected $_browser;

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
     * MAXLive_CreateFandVContracts::__construct()
     * Class constructor
     */
    public function __construct()
    {
        $ini = dirname(realpath(__FILE__)) . self::DS . self::INI_DIR . self::DS . self::INI_FILE;
        if (is_file($ini) === FALSE) {
            echo "File $ini not found. Please create it and populate it with the following data: username=x@y.com, password=`your password`, your name shown on MAX the welcome page welcome=`Joe Soap` and mode=`test` or `live`" . PHP_EOL;
            return FALSE;
        }
        $data = parse_ini_file($ini);
        if ((array_key_exists("proxy", $data)) && (array_key_exists("browser", $data) && $data["browser"]) && (array_key_exists("ip", $data) && $data["ip"]) && (array_key_exists("datadir", $data) && $data["datadir"]) && (array_key_exists("username", $data) && $data["username"]) && (array_key_exists("xls", $data) && $data["xls"]) && (array_key_exists("password", $data) && $data["password"]) && (array_key_exists("welcome", $data) && $data["welcome"]) && (array_key_exists("mode", $data) && $data["mode"])) {
            $this->_username = $data["username"];
            $this->_password = $data["password"];
            $this->_welcome = $data["welcome"];
            $this->_dataDir = $data["datadir"];
            $this->_errDir = $data["errordir"];
            $this->_scrDir = $data["screenshotdir"];
            $this->_xls = $data["xls"];
            $this->_ip = $data["ip"];
            $this->_proxyip = $data["proxy"];
            $this->_wdport = $data["wdport"];
            $this->_browser = $data["browser"];
            $this->_mode = $data["mode"];
            switch ($this->_mode) {
                case "live":
                    $this->_maxurl = self::LIVE_URL;
                    break;
                default:
                    $this->_maxurl = self::TEST_URL;
            }
        } else {
            echo "The correct data is not present in $ini. Please confirm the following fields are present in the file: username, password, welcome, mode, dataDir, ip, proxy, browser and xls." . PHP_EOL;
            return FALSE;
        }
    }

    /**
     * MAXLive_CreateFandVContracts::__destruct()
     * Class destructor
     * Allow for garbage collection
     */
    public function __destruct()
    {
        unset($this);
    }
    // : End
    
    /**
     * MAXLive_CreateFandVContracts::setUp()
     * Setup instance
     */
    public function setUp()
    {
        $wd_host = "http://localhost:$this->_wdport/wd/hub";
        self::$driver = new PHPWebDriver_WebDriver($wd_host);
        $desired_capabilities = array();
        
        /*
         * if ($this->_proxyip)
         * {
         * $proxy = new PHPWebDriver_WebDriverProxy();
         * $proxy->httpProxy = $this->_proxyip;
         * }
         *
         * $proxy->add_to_capabilities($desired_capabilities);
         */
        $this->_session = self::$driver->session($this->_browser, $desired_capabilities);
    }

    /**
     * MAXLive_CreateFandVContracts::testCreateContracts()
     * Pull F and V Contract data and automate creation of F and V Contracts
     */
    public function testCreateContracts()
    {
        
        // Initiate Session
        $session = $this->_session;
        $this->_session->setPageLoadTimeout(90);
        $w = new PHPWebDriver_WebDriverWait($this->_session);
        
        // : Login
        $this->_session->open($this->_maxurl);
        // : Wait for page to load and for elements toxs be present on page
        $e = $w->until(function ($session) {
            return $session->element('css selector', "#contentFrame");
        });
        $iframe = $this->_session->element('css selector', '#contentFrame');
        $this->_session->switch_to_frame($iframe);
        
        $e = $w->until(function ($session) {
            return $session->element('css selector', 'input[id=identification]');
        });
        // : End
        $this->assertElementPresent('css selector', 'input[id=identification]');
        $this->assertElementPresent('css selector', 'input[id=password]');
        $this->assertElementPresent('css selector', 'input[name=submit][type=submit]');
        
        $this->_session->element('css selector', 'input[id=identification]')->sendKeys($this->_username);
        $this->_session->element('css selector', 'input[id=password]')->sendKeys($this->_password);
        $this->_session->element('css selector', 'input[name=submit][type=submit]')->click();
        
        // Switch out of frame
        $this->_session->switch_to_frame();
        
        // : Wait for page to load and for elements to be present on page
        $e = $w->until(function ($session) {
            return $session->element('css selector', "#contentFrame");
        });
        $iframe = $this->_session->element('css selector', '#contentFrame');
        $this->_session->switch_to_frame($iframe);
        
        $e = $w->until(function ($session) {
            return $session->element("xpath", "//*[text()='" . $this->_welcome . "']");
        });
        $this->assertElementPresent("xpath", "//*[text()='" . $this->_welcome . "']");
        // Switch out of frame
        if ($this->_mode == "live") {
            $this->_session->switch_to_frame();
        }
        // : End
        
        // : Load Planningboard to rid of iframe loading on every page from here on
        $this->_session->open($this->_maxurl . self::PB_URL);
        $e = $w->until(function ($session) {
            return $session->element("xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]");
        });
        // : End
        
        // : Tear Down
        $this->_session->element('xpath', "//*[contains(@href,'/logout')]")->click();
        // Wait for page to load and for elements to be present on page
        $e = $w->until(function ($session) {
            return $session->element('css selector', 'input[id=identification]');
        });
        $this->assertElementPresent('css selector', 'input[id=identification]');
        $this->_session->close();
        // : End
    }
    
    // : Private Functions
    
    /**
     * MAXLive_CreateFandVContracts::assertElementPresent($_using, $_value)
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
    
    // : End
}
