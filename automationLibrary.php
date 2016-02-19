<?php
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
include_once dirname(__FILE__) . '/ReadExcelFile.php';
include_once 'PHPUnit/Extensions/PHPExcel/Classes/PHPExcel.php';
include_once 'PullDataFromMySQLQuery.php';

/**
 * automationLibrary.php
 *
 * @package automationLibrary
 * @author Clinton Wright <cwright@bwtsgroup.com>
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
class automationLibrary
{
    // : Constants
    
    // Constants - SQL Queries
    const SQL_QUERY_OBJREG = "select ID from objectregistry where handle = '%s';";

    const SQL_QUERY_ZONE = "select ID from udo_zone where name='%s';";

    const SQL_QUERY_ROUTE = "select ID from udo_route where locationFrom_id=%f and locationTo_id=%t;";

    const SQL_QUERY_RATE = "select ID from udo_rates where route_id=%ro and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and model='%m' and businessUnit_id=%b and rateType_id=%r;";

    const SQL_QUERY_CUSTOMER = "select ID from udo_customer where tradingName='%t';";

    const SQL_QUERY_OFFLOADING_CUSTOMER = "select ID from udo_offloadingcustomers where offloadingCustomer_id IN (select ID from udo_customer where tradingName='%o') and customer_id=%c;";

    const SQL_QUERY_CUSTOMER_LOCATION_LINK = "select ID from udo_customerlocations where location_id=%l and customer_id=%c;";

    const SQL_QUERY_LOCATION = "select ID from udo_location where name = '%n' and _type='%t';";

    const SQL_QUERY_TRUCK_TYPE = "select ID from udo_truckdescription where description='%d';";

    const SQL_QUERY_RATE_TYPE = "select ID from udo_ratetype where name='%s';";

    const SQL_QUERY_BUNIT = "select ID from udo_businessunit where name='%s';";

    const SQL_QUERY_CUSTOMER_LOCATION_BU_LINK = "select ID from udo_customerlocationsbusinessunit_link where customerLocations_id=%l and businessUnit_id=%b;";

    const SQL_QUERY_OFFLOAD_BU_LINK = "select ID from udo_offloadingcustomersbusinessunit_link where offloadingCustomers_id=%o and businessUnit_id=%b;";
    
    // Constants - Location Types
    const _TYPE_CITY = "udo_City";

    const _TYPE_CONTINENT = "udo_Continent";

    const _TYPE_DEPOT = "udo_Depot";

    const _TYPE_MILL = "udo_Mill";

    const _TYPE_PLANTATION = "udo_Plantation";

    const _TYPE_POINT = "udo_Point";

    const _TYPE_PROVINCE = "udo_Province";

    const _TYPE_SUBURB = "udo_Suburb";

    const _TYPE_TOLLGATE = "udo_TollGate";
    
    // Constants - Object Registry Objects
    const OBJREG_CUSTOMER = "udo_Customer";
    
    // Constants - Error Messages
    const ERR_COULD_NOT_FIND_ELEMENT = "ERROR: Could not find the expected element on page: %s";

    const ERR_NO_CUSTOMER_DATA = "FATAL: Could not find customer data when attempting to access the imported data from array.";

    const ERR_COULD_NOT_FIND_RECORD_USING_URL = "ERROR: Could not find %d after creating it using the following URL: %u";

    const ERR_PROCESS_FAILED_UNEXPECTEDLY = "ERROR: Caught error while busy with process %s with error message: %e";

    const ERR_NO_DATE_RANGE_VALUE = "ERROR: Could not find DateRangeValue for Record: %s";
    
    const ERR_COULD_NOT_OPEN_FILE = "ERROR: Could not open the specfied file %s";
    
    const ERR_FILE_NOT_FOUND = "ERROR: The following path and filename could not be found: %s";
    
    const ERR_FILE_EMPTY = "The following file is empty: %s";
    
    const ERR_COLUMN_VALIDATION_FAIL = "Not all columns are present in the following file %s";
    
    const ERR_MAX_NOT_RESPONDING = "MAX does not seem to be responding";
    
    const ERR_DIR_NOT_FOUND = "The specified directory was not found: %s";
    
    const ERR_DB_FAILED_TO_CONNECT = "ERROR: There was a problem connecting to the database. See error message: %s";
    
    const ERR_SQL_QUERY_NO_RESULTS_REQ_DATA = "FATAL: Required data searched from the database was not found using the following SQL query: %s";
    
    const ERR_FAILED_TO_LOGIN = "ERROR: Log into %h was unsuccessful. Please see the following error message relating to the problem: %s";
    
    // Constants - URL addresses
    const URL_RATE_DATAVIEW = "/DataBrowser?browsePrimaryObject=udo_Rates&browsePrimaryInstance=";
    
    const URL_CUSTOMER = "/DataBrowser?browsePrimaryObject=461&browsePrimaryInstance=";

    const URL_PB = "/Planningboard";

    const URL_POINT = "/Country_Tab/points?&tab_id=52";

    const URL_CITY = "/Country_Tab/cities?&tab_id=50";

    const URL_CUST_LOCATION_BU = "/DataBrowser?browsePrimaryObject=495&browsePrimaryInstance=";

    const URL_OFFLOAD_CUST_BU = "/DataBrowser?browsePrimaryObject=494&browsePrimaryInstance=%d&browseSecondaryObject=989&useDataViewForSecondary=897&tab_id=";

    const URL_RATEVAL = "/DataBrowser?&browsePrimaryObject=udo_Rates&browsePrimaryInstance=%s&browseSecondaryObject=DateRangeValue&relationshipType=Rate";

    const URL_LIVE = "https://login.max.bwtsgroup.com";

    const URL_TEST = "http://max.mobilize.biz";
    
    const URL_LIVE_V3 = "https://max.bwtrans.co.za";
    
    const URL_TEST_V3 = "http://max3.mobilize.biz";

    const URL_API_GET = "/api_request/Data/get?objectRegistry=";
    
    const URL_LOCATION_ROUTE = "/Country_Tab/routes?&tab_id=113";
    
    // Constants - Paths
    const PATH_CONFIG_DIR = "config";
    
	// Constants - Filenames
	const FILE_CONFIG = "selenium_config.json";
    
    // Constants - Miscellaneous
    const DEFAULT_MAX_VERSION = 2;
    
    const CSV_DELIMITER = ',';

    const CSV_ENCLOSURE = '"';

    const CSV_LIMIT = 0;
    
    const DEFAULT_PATH_ENV_VAR = 'BWT_MAX_SELENIUM_PATH';
   
    const DS = DIRECTORY_SEPARATOR;
    // : End
    
    // : Properties
    public $_sessionObj;
    public $_phpunitObj;
    public $_wObj;
    public $pdoobj;
    
    protected $_mode;
    protected $_version;
    protected static $_config_array = array();
	protected static $_config_array_defaults = array(
		'selenium' => array(
			'username' => '',
			'password' => '',
			'welcome' => '',
			'mode' => '',
			'path_data' => '',
			'path_errors' => '',
			'path_screenshots' => '',
			'proxy' => '',
			'wdport' => '',
			'browser' => '',
			'jenkins' => '',
			'apiuserpwd' => '',
			'maxdbtenant' => ''
		)
	);
    
    // : End
    
    // : Magic Methods
    
    /**
     * automationLibrary::__construct(&$_session, &$_phpunit_fw_obj, $_w, $_mode, $_version)
     * Class constructor
     */
    public function __construct(&$_session, &$_phpunit_fw_obj, &$_w, $_mode, $_version) {

        if (is_object($_session) && is_object($_phpunit_fw_obj) && $_w && $_mode && $_version) {
			
            // : Save referenced session and phpunit objects to affect the referenced active session been passed
            $this->_sessionObj = $_session;
            $this->_phpunitObj = $_phpunit_fw_obj;
            $this->_wObj = $_w;
            // : End
    
            // : Save some local object instance variables
            $this->_mode = $_mode;
            $this->_version = $_version;
            // : End
            
            // : Set config array defaults
            if (count(automationLibrary::$_config_array) == 0)
            {
				self::setDefaultConfigOptions(automationLibrary::$_config_array_defaults);
            
				// Check if mysql class has method to fetch its config options
				if (function_exists(PullDataFromMySQLQuery::getDefaultConfigOptions))
				{
					$_db_config = PullDataFromMySQLQuery::getDefaultConfigOptions();
				
					if (is_array($_db_config) && count($_db_config) > 0)
					{
						$_new_config = automationLibrary::mergeConfigOptions($_config);
					
						if (is_array($_new_config) && count($_new_config) > 0)
						{
							self::setDefaultConfigOptions(automationLibrary::$_new_config);
						}
					}
				}
			}
        }
    }
    
    /**
     * automationLibrary::__destruct()
     * Class destructor
     */
    public function __destruct() {
        unset($this);
    }
    // : End
    
    // : Public Methods
    
    /**
     * automationLibrary::CONSOLE_OUTPUT($_heading, $_description, $_type, $_query, $_data)
     * Output debug information onto screen
     * Heading: What debug information we are displaying title
     * Description: A short description about the debug information
     * Type: Only 'sql' type available at the moment for displaying SQL Debug Data
     * Query: SQL query ran
     * Data: SQL results returned from the above query
     *
     * @param string: $_heading            
     * @param string: $_description            
     * @param string: $_type            
     * @param string: $_query            
     * @param array: $_data            
     */
    public function CONSOLE_OUTPUT($_heading, $_description, $_type, $_query, $_data)
    {
        switch ($_type) {
            case "sql":
            default:
                {
                    printf("INFO: %s. Query run: %s" . PHP_EOL, $_heading, $_query);
                    printf("DEBUG: %s" . PHP_EOL, $_description);
                    var_dump($_data);
                }
        }
    }
    
    /**
     * automationLibrary::getMAXURL($_mode, $_version)
     * 
     * Fetch the base URL for required version of MAX
     *
     * @param string: $_mode            
     * @param string: $_version
     * @param return: $_result
     */
    public static function getMAXURL($_mode, $_version) {
        $_result = (string)"";
        
        if ($_mode == "live" && $_version == 2) {
            $_result = self::URL_LIVE;
        } else if ($_mode == "test" && $_version == 2) {
            $_result = self::URL_TEST;
        } else if ($_mode == "live" && $_version == 3) {
            $_result = self::URL_LIVE_V3;
        } else if ($_mode == "test" && $_version == 3) {
            $_result = self::URL_TEST_V3;
        } else {
            $_result = FALSE;
        }
        return $_result;
    }
    
    /**
     * automationLibrary::getDefaultConfigOptionsArray()
     * 
     * Fetch the default config options in an array form
     *
     * @param return: $this->_config_array_defaults
     */
    public static function getDefaultConfigOptionsArray()
    {
		return automationLibrary::$_config_array;
	}
	
    /**
     * automationLibrary::getConfigFilePath()
     * 
     * Fetch the default config options in an array form
     *
     * @param return: $this->_config_array_defaults
     */
    public static function getConfigFilePath()
    {
		$_filepath = getenv(self::DEFAULT_PATH_ENV_VAR) . self::DS . self::PATH_CONFIG_DIR . self::DS . self::FILE_CONFIG;
		
		if ($_filepath && is_string($_filepath))
		{
			return $_filepath;
		} else
		{
			return false;
		}
	}
	
    /**
     * automationLibrary::mergeConfigOptions()
     * 
     * Fetch the default config options and merge with the required
     * array argument and return the merged result
     *
     * @param return: $this->_config_array_defaults
     */
    public static function mergeConfigOptions($_config_array)
    {
		if (is_array($_config_array))
		{
			// Merge default config array and supplied config array
			$_result = array_merge_recursive($_config_array, automationLibrary::$_config_array);
			
			if (is_array($_result) && count($_result) >= 1)
			{
				return $_result;
			} else
			{
				return automationLibrary::$_config_array_defaults;
			}
			
		} else
		{
			return automationLibrary::$_config_array_defaults;
		}
	}
    
    /**
     * automationLibrary::addErrorRecord(&$_errArr, $_scrDir, $_errmsg, $_record, $_process)
     * Add error record to error array
     *
     * @param array: $_erArrr
     * @param object: $this->_sessionObj
     * @param string: $_scrDir
     * @param string: $_errmsg
     * @param string: $_record
     * @param string: $_process
     */
    public function addErrorRecord(&$_errArr, $_scrDir, $_errmsg, $_record, $_process)
    {
        $_erCount = count($_errArr);
        $_errArr[$_erCount + 1]["error"] = $_errmsg;
        $_errArr[$_erCount + 1]["record"] = $_record;
        $_errArr[$_erCount + 1]["type"] = $_process;
        $this->takeScreenshot($_scrDir);
    }
    
    /**
     * automationLibrary::stringHypenFix($_value)
     * Replace long hyphens in string to short hyphens as part of a problem
     * created when importing data from spreadsheets
     *
     * @param string: $_value            
     * @param string: $_result            
     */
    public function initDB($_tenant, $_config_data)
    {
		$_config_file = self::getConfigFilePath();
		
		if ($_config_file && $_tenant && is_string($_tenant))
		{
			$this->pdoobj = new PullDataFromMySQLQuery($_tenant, $_config_data);
		}
	} 

    /**
     * automationLibrary::stringHypenFix($_value)
     * Replace long hyphens in string to short hyphens as part of a problem
     * created when importing data from spreadsheets
     *
     * @param string: $_value            
     * @param string: $_result            
     */
    public function stringHypenFix($_value)
    {
        $_result = preg_replace("/–/", "-", $_value);
        return $_result;
    }

    /**
     * automationLibrary::getSelectedOptionValue($_using, $_value, &$this->_sessionObj)
     * This is a function description for a selenium test function
     *
     * @param string: $_using            
     * @param string: $_value            
     * @param object: $this->_sessionObj            
     */
    public function getSelectedOptionValue($_using, $_value)
    {
        try {
            $_result = FALSE;
            $_cnt = count($this->_sessionObj->elements($_using, $_value));
            for ($x = 1; $x <= $_cnt; $x ++) {
                $_selected = $this->_sessionObj->element($_using, $_value . "[$x]")->attribute("selected");
                if ($_selected) {
                    $_result = $this->_sessionObj->element($_using, $_value . "[$x]")->attribute("value");
                    break;
                }
            }
        } catch (Exception $e) {
            $_result = FALSE;
        }
        return ($_result);
    }

    /**
     * automationLibrary::assertElementPresent($_using, $_value, &$this->_sessionObj, &$this->_phpunitObj)
     * This is a function description for a selenium test function
     *
     * @param string: $_using            
     * @param string: $_value            
     * @param object: $this->_sessionObj            
     * @param object: $this->_phpunitObj            
     */
    public function assertElementPresent($_using, $_value)
    {
        $e = $this->_sessionObj->element($_using, $_value);
        try {
            $this->_phpunitObj->assertEquals(count($e), 1);
        } catch (Exception $e) {
            return FALSE;
        }
        return TRUE;
    }
    
    public static function initializeConfig()
    {
		self::setDefaultConfigOptions(automationLibrary::$_config_array_defaults);
            
		// Check if mysql class has method to fetch its config options
		$_db_config = PullDataFromMySQLQuery::getDefaultConfigOptions();
		
		if (is_array($_db_config) && count($_db_config) > 0)
		{
			$_new_config = automationLibrary::mergeConfigOptions($_db_config, automationLibrary::$_config_array);
			
			if (is_array($_new_config) && count($_new_config) > 0)
			{
				self::setDefaultConfigOptions($_new_config);
			}
		}
	}
    
    /**
     * automationLibrary::takeScreenshot()
     * This is a function description for a selenium test function
     *
     * @param object: $_session
     */
    public function takeScreenshot($_scrDir)
    {
        $_params = func_get_args();
        $_img = $this->_sessionObj->screenshot();
        $_data = base64_decode($_img);
        $_pathname_extra = (string) "";
    
        if ($_params && is_array($_params)) {
            if (array_key_exists(2, $_params)) {
                $_pathname_extra = $_params[2];
            }
        }
        // Suport for variable length arguments (only 1 extra argument supported
        if ($_pathname_extra) {
            $_file = $_scrDir . DIRECTORY_SEPARATOR . date("Y-m-d_His") . "_${_pathname_extra}_WebDriver.png";
    } else {
        $_file = $_scrDir . DIRECTORY_SEPARATOR . date("Y-m-d_His") . "_WebDriver.png";
    }
    $_success = file_put_contents($_file, $_data);
    if ($_success) {
        return $_file;
    } else {
        return FALSE;
    }
    }

    /**
     * automationLibrary::writeExcelFile($excelFile, $excelData)
     * Create, Write and Save Excel Spreadsheet from collected data obtained from the variance report
     *
     * @param $excelFile, $excelData            
     */
    public function writeExcelFile($excelFile, $excelData, $columns, $author = NULL, $title = NULL, $subject = NULL)
    {
        try {
            // Check data validility
            if (count($excelData) != 0) {
                
                // : Create new PHPExcel object
                print("<pre>");
                print(date('H:i:s') . " Create new PHPExcel object" . PHP_EOL);
                $objPHPExcel = new PHPExcel();
                // : End
                
                // : Set properties
                print(date('H:i:s') . " Set properties" . PHP_EOL);
                if ($author) {
                    $objPHPExcel->getProperties()->setCreator($author);
                    $objPHPExcel->getProperties()->setLastModifiedBy($author);
                }
                if ($title) {
                    $objPHPExcel->getProperties()->setTitle($title);
                }
                if ($subject) {
                    $objPHPExcel->getProperties()->setSubject($subject);
                }
                // : End
                
                // : Setup Workbook Preferences
                print(date('H:i:s') . " Setup workbook preferences" . PHP_EOL);
                $objPHPExcel->getDefaultStyle()
                    ->getFont()
                    ->setName('Arial');
                $objPHPExcel->getDefaultStyle()
                    ->getFont()
                    ->setSize(8);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setFitToWidth(1);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setFitToHeight(0);
                // : End
                
                // : Set Column Headers
                $alphaVar = range('A', 'Z');
                print(date('H:i:s') . " Setup column headers" . PHP_EOL);
                
                $i = 0;
                foreach ($columns as $key) {
                    $objPHPExcel->getActiveSheet()->setCellValue($alphaVar[$i] . "1", $key);
                    $objPHPExcel->getActiveSheet()
                        ->getStyle($alphaVar[$i] . '1')
                        ->getFont()
                        ->setBold(true);
                    $i ++;
                }
                
                // : End
                
                // : Add data from $excelData array
                print(date('H:i:s') . " Add data from error array" . PHP_EOL);
                $rowCount = (int) 2;
                $objPHPExcel->setActiveSheetIndex(0);
                foreach ($excelData as $values) {
                    $i = 0;
                    foreach ($values as $key => $value) {
                        $objPHPExcel->getActiveSheet()
                            ->getCell($alphaVar[$i] . strval($rowCount))
                            ->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                        $i ++;
                    }
                    $rowCount ++;
                }
                // : End
                
                // : Setup Column Widths
                for ($i = 0; $i <= count($columns); $i ++) {
                    $objPHPExcel->getActiveSheet()
                        ->getColumnDimension($alphaVar[$i])
                        ->setAutoSize(true);
                }
                // : End
                
                // : Rename sheet
                print(date('H:i:s') . " Rename sheet" . PHP_EOL);
                $objPHPExcel->getActiveSheet()->setTitle($title);
                // : End
                
                // : Save spreadsheet to Excel 2007 file format
                print(date('H:i:s') . " Write to Excel2007 format" . PHP_EOL);
                print("</pre>" . PHP_EOL);
                $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
                $objWriter->save($excelFile);
                $objPHPExcel->disconnectWorksheets();
                unset($objPHPExcel);
                unset($objWriter);
                // : End
            } else {
                print("<pre>");
                print_r("ERROR: The function was passed an empty array");
                print("</pre>");
            }
        } catch (Exception $e) {
            echo "Caught exception: ", $e->getMessage(), "\n";
        }
    }
        
    /**
     * automationLibrary::ImportCSVFileIntoArray($csvFile)
     * From supplied csv file save data into multidimensional array
     *
     * @param string: $csvFile            
     * @param array: $_result            
     */
    public function ImportCSVFileIntoArray($csvFile)
    {
        try {
            $_data = (array) array();
            $_header = NULL;
            
            if (file_exists($csvFile)) {
				
                if (($_handle = fopen($csvFile, 'r')) !== FALSE) {
					
                    while (($_row = fgetcsv($_handle, self::CSV_LIMIT, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) !== FALSE) {
						
                        if (! $_header) {
							
                            foreach ($_row as $_value) {
								
                                $_header[] = strtolower($_value);
                            }
                            
                        } else {
							
                            $_data[] = array_combine($_header, $_row);
                            
                        }
                    }
                    
                    // Close file handler
                    fclose($_handle);
                    
                    if (count($_data) != 0) {
                        
                        foreach ($_data as $_key => $_value) {
							
                            foreach ($_value as $_keyA => $_valueA) {
								
                                $_data[$_key][$_keyA] = $this->stringHypenFix($_valueA);
                                
                            }
                        }
                        
                        return $_data;
                    } else {
                        $_msg = preg_replace("@%s@", $csvFile, self::ERR_FILE_EMPTY);
                        throw new Exception($_msg);
                    }
                } else {
                    $_msg = preg_replace("@%s@", $csvFile, self::ERR_COULD_NOT_OPEN_FILE);
                    throw new Exception($_msg);
                }
            } else {
                $_msg = preg_replace("@%s@", $csvFile, self::ERR_FILE_NOT_FOUND);
                throw new Exception($_msg);
            }
        } catch (Exception $e) {
             echo "Caught exception: ", $e->getMessage(), "\n";
             return FALSE;
        }
    }
 
	/**
	 * automationLibrary::ExportToCSV($csvFile, $arr)
	 * From supplied csv file save data into multidimensional array
	 *
	 * @param string: $csvFile
	 * @param array: $_arr
	 */
	public function ExportToCSV($csvFile, $_arr) {
		try {
			$_data = ( array ) array ();
			
			if (file_exists ( dirname ( $csvFile ) )) {
				
				$_handle = fopen ( $csvFile, 'w' );
				
				foreach ( $_arr as $key => $value ) {
					
					fputcsv ( $_handle, $value );
					
				}
				
				fclose ( $_handle );
				
			} else {
				
				$_msg = preg_replace ( "@%s@", dirname($csvFile), self::ERR_DIR_NOT_FOUND );
				throw new Exception ( $_msg );
				
			}
		} catch ( Exception $e ) {
			return FALSE;
		}
	}
	
    /**
     * automationLibrary::LoadJSONFile($_file)
     * Load config file containing json data
     *
     * @param return: $_result   
     */	
	public static function LoadJSONFile($_file)
	{
		// Default _result to FALSE
		$_result = false;
		
		try
		{
		
		if (file_exists($_file))
		{
			$_json_file = file_get_contents($_file);
			
			if ($_json_file)
			{
				$_json_data = json_decode($_json_file, true);
				
				if ($_json_data && is_array($_json_data))
				{
					$_result = $_json_data;
				}
			}
		}
		} catch (Exception $e)
		{
             echo "Caught exception: ", $e->getMessage(), "\n";
             return false;
		}
		
		return $_result;
	}

	/**
     * automationLibrary::verifyKeysMatchInArrays($_array1, $_array2)
     * Using a multidimensional passed as an argument
     * Verify all keys are present and return values for each key
     *
     * @param return: $_result   
     */	
	public static function verifyKeysMatchInArrays($_array1, $_array2, $_count = 1)
	{
		
		$_pass = true;
		$_array = (array) array();
		
		if (is_array($_array1) && is_array($_array2))
		{
			foreach($_array1 as $key => $value)
			{
				if (is_array($value))
				{

					$_result = self::verifyKeysMatchInArrays($value, $_array2[$key], ++$_count);
					
					if (is_array($_result))
					{
						$_array[$key] = $_array2[$key];
					} else
					{
						$_pass = false;
					}
					
				} else
				{

					if (array_key_exists($key, $_array2))
					{
						$_array[$key] = $value;
					}
				}
			}
		}

		if (is_array($_array))
		{
			if (count(array_diff_key($_array, $_array1)) == 0 && $_pass)
			{
				return $_array;
			} else
			{
				return false;
			}
		}
	}
	
	/**
     * automationLibrary::verifyAndLoadConfig($_config_array)
     * Using a multidimensional passed as an argument
     * Verify all keys are present and return values for each key
     *
     * @param return: $_result   
     */	
	public static function verifyAndLoadConfig($_config_array, $_file)
	{
		
		$_json_config_data = automationLibrary::LoadJSONFile($_file);

		if ($_json_config_data)
		{
			
			$_result = automationLibrary::verifyKeysMatchInArrays($_config_array, $_json_config_data);
			
			if ($_result && is_array($_result))
			{
				return $_result;
			}
		}
		
		return false;
	}
	// : Private Methods
	
	/**
     * automationLibrary::setDefaultConfigOptions($_config_array)
     * Using a multidimensional passed as an argument
     * Verify all keys are present and return values for each key
     *
     * @param return: $_result
     */	
	public static function setDefaultConfigOptions($_config_array)
	{
		// Verify that default keys exist
		$_result = self::verifyKeysMatchInArrays(automationLibrary::$_config_array_defaults, $_config_array);
		
		if (is_array($_result))
		{
			automationLibrary::$_config_array = $_config_array;
		}
	}
	
    // : End
}
