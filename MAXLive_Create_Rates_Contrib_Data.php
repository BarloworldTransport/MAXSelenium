<?php
// Set error reporting level for this script
error_reporting ( E_ALL );

// : Includes

require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
require_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
require_once 'automationLibrary.php';
require_once 'MAX_LoginLogout.php';

// : End

/**
 * MAXTest_Fleet_Contrib_Data.php
 *
 * @package MAXTest_Fleet_Contrib_Data
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
class MAXTest_Fleet_Contrib_Data extends PHPUnit_Framework_TestCase {
	// : Constants
	// All inherited from automationLibrary
	
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
	protected $_file;
	protected $_config;
	protected $_max_db_tenant;
	
	// : Public Functions
	// : Accessors
	// : End
	
	// : Magic
	/**
	 * MAXTest_Fleet_Contrib_Data::__construct()
	 * Class constructor
	 */
	public function __construct() {
		
		// Define required config options for this script
		$_config = (array) array(
			'selenium' => array(
				'rates' => array(
					'file' => '',
					'filetype' => ''
				)
			)
		);
		
		// Initialize config
		automationLibrary::initializeConfig();
		
		// Merge config with default config options
		$_config = automationLibrary::mergeConfigOptions($_config);
		
		// Determine full path to the json config file
		$_config_file =  automationLibrary::getConfigFilePath();

		if (is_file ( $_config_file ) === FALSE) {
			echo "No " . $_config_file . " file found. Please create it and populate it with the following data: username=x@y.com, password=`your password`, your name shown on MAX the welcome page welcome=`Joe Soap` and mode=`test` or `live`" . PHP_EOL;
			return FALSE;
		}
		
		// Load config
		$_config = automationLibrary::verifyAndLoadConfig($_config, $_config_file);
		
		// : Save variables from fetched config data for use in script
		if ($_config)
		{
				$this->_config = $_config;
				$this->_username = $_config['selenium']['username'];
				$this->_password = $_config['selenium']['password'];
				$this->_welcome = $_config['selenium']['welcome'];
				$this->_mode = $_config['selenium']['mode'];
				$this->_wdport = $_config['selenium']['wdport'];
				$this->_proxyip = $_config['selenium']['proxy'];
				$this->_browser = $_config['selenium']['browser'];
				$this->_datadir = $_config['selenium']['path_data'];
				$this->_scrdir = $_config['selenium']['path_screenshots'];
				$this->_errdir = $_config['selenium']['path_errors'];
				$this->_version = $_config['selenium']['version'];
				$this->_apiuserpwd = $_config['selenium']['apiuserpwd'];
				$this->_file = $_config['selenium']['rates']['file'];
				$this->_max_db_tenant = $_config['selenium']['maxdbtenant'];
			
				// Determine MAX URL to be used for this test run
				$this->_maxurl = automationLibrary::getMAXURL($this->_mode, $this->_version);
		} else
		{
			print("Required key value pairs where not found in the json config file: $_config_file" . PHP_EOL);
			exit;
		}
		// : End

	}
	
	/**
	 * MAXTest_Fleet_Contrib_Data::__destruct()
	 * Class destructor
	 * Allow for garbage collection
	 */
	public function __destruct() {
		unset ( $this );
	}
	// : End
	public function _runArrayRecur($_xmlObject, $_instCnt = 0) {
		$_array = array();
		foreach ( (array) $_xmlObject as $_index => $_node) {
			if ($_instCnt < 100) {
				$_array[$_index] = is_object($_node) ? $this->_runArrayRecur($_node, $_instCnt++) : $_node;
			} 
		}
		return $_array;
	}
	
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
	 * MAXTest_Fleet_Contrib_Data::testCreateRateContribRecords
	 * Automation script that creates contribution data for rates
	 */
	public function testCreateRateContribRecords() {
		try {
			
			$_data_file = getenv(automationLibrary::DEFAULT_PATH_ENV_VAR) . automationLibrary::DS . $this->_datadir . automationLibrary::DS . $this->_file;
			
			if (file_exists($_data_file))
			{
				
				// Initialize session
				$session = $this->_session;
				$this->_session->setPageLoadTimeout ( 60 );
				$w = new PHPWebDriver_WebDriverWait ( $session, 30 );
			
				// Initialize automation library instance
				$_autoLib = new automationLibrary($this->_session, $this, $w, $this->_mode, $this->_version);
			
				// Load data from csv file
				$this->_data = $_autoLib->ImportCSVFileIntoArray($_data_file);
				
				// Initialize connection to the MAX database -> PDO db object available in $_autoLib->pdoobj
				$_autoLib->initDB($this->_max_db_tenant, $this->_config);
				
				$_db_errors = $_autoLib->pdoobj->getErrors();
				
				if (count($_db_errors) > 0)
				{
					print("Failed to connect to the database");
					
					foreach ($_db_errors as $_key => $_value)
					{
						printf("Error %d: %s %s", $_key, $_value, PHP_EOL);
					}
					
					throw new Exception("Failed to connect to database");
				}
				/** EXAMPLE CODE TO QUERY MAX DB
				$_testquery = "SELECT * FROM udo_cargo ORDER BY ID DESC LIMIT 1";
				$_db_result = $_autoLib->pdoobj->getDataFromQuery($_testquery);
				*/
				
				/** THIS LOOPS THE EACH RECORD AND RUNS REQUIRED CODE TO EXECUTE ACTION REQUIRED
				if ($this->_data && is_array($this->_data))
				{
					foreach ($this->_data $key => $value)
					{
						
					}
				}*/

				$_maxLoginLogout = new maxLoginLogout($_autoLib, $this->_maxurl);
			
				// Log into MAX
				if (!$_maxLoginLogout->maxLogin($this->_username, $this->_password, $this->_welcome)) {
					throw new Exception($_maxLoginLogout->getLastError());
				}
			

				// Log out of MAX
				$_maxLoginLogout->maxLogout($this->_session, $w, $this, $this->_version);
				$this->_session->close();
			} else
			{
				print("Data file does not exist that has been specified in the json config file: $this->_file" . PHP_EOL);
				exit;
			}
			
		} catch ( Exception $e ) {
			
			$_errmsg = preg_replace ( "/%h/", $this->_maxurl, automationLibrary::ERR_FAILED_TO_LOGIN );
			$_errmsg = preg_replace ( "/%s/", $e->getMessage (), $_errmsg );
			throw new Exception ( $_errmsg );
			unset ( $_errmsg );
			
		}
	}
}
