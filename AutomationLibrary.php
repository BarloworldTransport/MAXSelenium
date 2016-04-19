<?php
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
include_once dirname(__FILE__) . '/ReadExcelFile.php';
include_once 'PHPUnit/Extensions/PHPExcel/Classes/PHPExcel.php';
include_once 'PullDataFromMySQLQuery.php';

/**
 * AutomationLibrary.php
 *
 * @package AutomationLibrary
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
class AutomationLibrary
{
    // : Constants
    
    // Constants - SQL Queries
    const SQL_QUERY_OBJREG = "select ID from objectregistry where handle = '%s';";

    const SQL_QUERY_ZONE = "select ID from udo_zone where name='%s';";

    const SQL_QUERY_ROUTE = "select ID from udo_route where locationFrom_id=%f and locationTo_id=%t;";

    const SQL_QUERY_RATE = "select ID from udo_rates where route_id=%ro and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and model='%m' and businessUnit_id=%b and rateType_id=%r;";
    
    const SQL_QUERY_RATE_TRIMMED = "select ID from udo_rates where route_id=%ro and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and businessUnit_id=%b;";

    const SQL_QUERY_CUSTOMER = "select ID from udo_customer where tradingName='%t';";

    const SQL_QUERY_OFFLOADING_CUSTOMER = "select ID from udo_offloadingcustomers where offloadingCustomer_id IN (select ID from udo_customer where tradingName='%o') and customer_id=%c;";

    const SQL_QUERY_CUSTOMER_LOCATION_LINK = "select ID from udo_customerlocations where location_id=%l and customer_id=%c;";

    const SQL_QUERY_LOCATION = "select ID from udo_location where name like '%n' and _type like '%t';";

    const SQL_QUERY_TRUCK_TYPE = "select ID from udo_truckdescription where description='%d';";

    const SQL_QUERY_RATE_TYPE = "select ID from udo_ratetype where name='%s';";

    const SQL_QUERY_BUNIT = "select ID from udo_businessunit where name='%s';";

    const SQL_QUERY_CUSTOMER_LOCATION_BU_LINK = "select ID from udo_customerlocationsbusinessunit_link where customerLocations_id=%l and businessUnit_id=%b;";

    const SQL_QUERY_OFFLOAD_BU_LINK = "select ID from udo_offloadingcustomersbusinessunit_link where offloadingCustomers_id=%o and businessUnit_id=%b;";
    
    const SQL_QUERY_DRV = "select ID from daterangevalue where objectregistry_id=%g and objectInstanceId=%r and type='%t'";
    
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
    
    const OBJREG_DATERANGEVALUE = "DateRangeValue";
    
    const OBJREG_RATE = "udo_Rates";
    
    // Constants - Location Types
    const UDO_LOCATION_TYPE_CITY = 'City';
    
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
    
    const ERR_DB_NOT_CONNECTED = "ERROR: Database connection has not been established but attempting to run a query";
    
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
    public $pdoobj = false;
    
    protected $_dm = false;
    protected $_dm_mode = 'curl';
    protected $_errors;
    protected $_mode;
    protected $_version;
    protected $_reports = array();
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
     * AutomationLibrary::__construct(&$_session, &$_phpunit_fw_obj, $_w, $_mode, $_version)
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
            if (count(AutomationLibrary::$_config_array) == 0)
            {
				self::setDefaultConfigOptions(AutomationLibrary::$_config_array_defaults);
            
				// Check if mysql class has method to fetch its config options
				if (function_exists(PullDataFromMySQLQuery::getDefaultConfigOptions))
				{
					$_db_config = PullDataFromMySQLQuery::getDefaultConfigOptions();
				
					if (is_array($_db_config) && count($_db_config) > 0)
					{
						$_new_config = AutomationLibrary::mergeConfigOptions($_config);
					
						if (is_array($_new_config) && count($_new_config) > 0)
						{
							self::setDefaultConfigOptions(AutomationLibrary::$_new_config);
						}
					}
				}
			}
        }
    }
    
    /**
     * AutomationLibrary::__destruct()
     * Class destructor
     */
    public function __destruct() {
        unset($this);
    }
    // : End
    
    // : Public Methods
    
    /**
     * AutomationLibrary::getErrors()
     * Return all recorded errors
     */
    public function getErrors()
    {
		if ($this->_errors && is_array($this->_errors))
		{
			return $this->_errors;
		}
		
		return false;
	}

    /**
     * AutomationLibrary::getReports()
     * Return report data from last updated report
     */
    public function getReports()
    {
		if ($this->_reports && is_array($this->_reports))
		{
			return $this->_reports;
		}
		
		return false;
	}
	
    /**
     * AutomationLibrary::setReports($_reports_arr)
     * Overwrite reports with a new report
     */
    public function setReports($_reports_arr)
    {
		if ($_reports_arr && is_array($_reports_arr))
		{
			$this->_reports[] = $_reports_arr;
			return true;
		}
		
		return false;
	}
	
    /**
     * AutomationLibrary::checkForRequiredEnv()
     * Return report data from last updated report
     */
    public static function checkForRequiredEnv()
    {
		if (getenv(self::DEFAULT_PATH_ENV_VAR))
		{
			return true;
		}
		
		return false;
	}

    /**
     * AutomationLibrary::dmGetMode()
     * Return Data Manager mode
     */	
	public function dmGetMode()
	{
		if ($this->_dm_mode && is_string($this->_dm_mode))
		{
			return $this->_dm_mode;
		}
		return FALSE;
	}
	
    /**
     * AutomationLibrary::dmGetStatus()
     * Return Data Manager status
     */	
	public function dmGetStatus()
	{
		return bool($this->_dm);
	}
	
    /**
     * AutomationLibrary::dmInit()
     * Data Manager initialize
     * 
     * @param 
     */	
	public function dmInit()
	{
		if ($this->_dm_mode && is_string($this->_dm_mode)) {
			
		}
	}

    /**
     * AutomationLibrary::fetchObjectRegistryId($_object_registry_name)
     * Query MAX DB to fetch the id for a object registry item
     */
    public function fetchObjectRegistryId($_object_reg)
    {
		if ($this->getDbStatus() != false)
		{
			if ($_object_reg && is_string($_object_reg))
			{
				$_query = preg_replace("@%s@", $_object_reg, AutomationLibrary::SQL_QUERY_OBJREG);
			
				if ($_query && is_string($_query))
				{
					$_sql_result = $this->pdoobj->getDataFromQuery($_query);
				
					if ($_sql_result && is_array($_sql_result))
					{
						if (isset($_sql_result[0]['ID']))
						{
							if ($_sql_result[0]['ID'])
							{
								return $_sql_result[0]['ID'];
							}
						}
					}
				}
			}
		} else
		{
			$this->_errors[] = AutomationLibrary::ERR_DB_NOT_CONNECTED;
		}
		
		// If code reaches this point then query failed
		return false;
	}
	
    /**
     * AutomationLibrary::fetchTruckDescriptionId
     * Query MAX DB to fetch the id for a truck description
     */
    public function fetchTruckDescriptionId($_truckDescription)
    {
		if ($this->getDbStatus() != false)
		{
			if ($_truckDescription && is_string($_truckDescription))
			{
				$_query = preg_replace("@%d@", $_truckDescription, AutomationLibrary::SQL_QUERY_TRUCK_TYPE);
			
				if ($_query && is_string($_query))
				{
					$_sql_result = $this->pdoobj->getDataFromQuery($_query);
				
					if ($_sql_result && is_array($_sql_result))
					{
						if (isset($_sql_result[0]['ID']))
						{
							if ($_sql_result[0]['ID'])
							{
								return $_sql_result[0]['ID'];
							}
						}
					}
				}
			}
		} else
		{
			$this->_errors[] = AutomationLibrary::ERR_DB_NOT_CONNECTED;
		}
		
		// If code reaches this point then query failed
		return false;
	}
	
    /**
     * AutomationLibrary::fetchBusinessUnitId($_bu)
     * Query MAX DB to fetch the id for a business unit
     */
    public function fetchBusinessUnitId($_bu)
    {
		if ($this->getDbStatus() != false)
		{
			if ($_bu && is_string($_bu))
			{
				$_query = preg_replace("@%s@", $_bu, AutomationLibrary::SQL_QUERY_BUNIT);
			
				if ($_query && is_string($_query))
				{
					$_sql_result = $this->pdoobj->getDataFromQuery($_query);
				
					if ($_sql_result && is_array($_sql_result))
					{
						if (isset($_sql_result[0]['ID']))
						{
							if ($_sql_result[0]['ID'])
							{
								return $_sql_result[0]['ID'];
							}
						}
					}
				}
			}
		} else
		{
			$this->_errors[] = AutomationLibrary::ERR_DB_NOT_CONNECTED;
		}
		
		// If code reaches this point then query failed
		return false;
	}

    /**
     * AutomationLibrary::fetchLocationId($_location, $_type)
     * Query MAX DB to fetch the id for a location
     */
    public function fetchLocationId($_location, $_type)
    {
		if ($this->getDbStatus() != false)
		{
			if ($_location && is_string($_location) && $_type && is_string($_type))
			{
				$_query = preg_replace("@%n@", $_location, AutomationLibrary::SQL_QUERY_LOCATION);
				$_query = preg_replace("@%t@", "%" . $_type . "%", $_query);
			
				if ($_query && is_string($_query))
				{
					$_sql_result = $this->pdoobj->getDataFromQuery($_query);
				
					if ($_sql_result && is_array($_sql_result))
					{
						if (isset($_sql_result[0]['ID']))
						{
							if ($_sql_result[0]['ID'])
							{
								return $_sql_result[0]['ID'];
							}
						}
					}
				}
			}
		} else
		{
			$this->_errors[] = AutomationLibrary::ERR_DB_NOT_CONNECTED;
		}
		
		// If code reaches this point then query failed
		return false;
	}
	
    /**
     * AutomationLibrary::fetchRouteId
     * Query MAX DB to fetch the id for a route
     * 	)
     */
    public function fetchRouteId($_locationFromId, $_locationToId)
    {
		if ($this->getDbStatus() != false)
		{
			if (intval($_locationFromId) && intval($_locationToId))
			{
				$_query = preg_replace("@%f@", $_locationFromId, AutomationLibrary::SQL_QUERY_ROUTE);
				$_query = preg_replace("@%t@", $_locationToId, $_query);
			
				if ($_query && is_string($_query))
				{
					$_sql_result = $this->pdoobj->getDataFromQuery($_query);
				
					if ($_sql_result && is_array($_sql_result))
					{
						if (isset($_sql_result[0]['ID']))
						{
							if ($_sql_result[0]['ID'])
							{
								return $_sql_result[0]['ID'];
							}
						}
					}
				}
			}
		} else
		{
			$this->_errors[] = AutomationLibrary::ERR_DB_NOT_CONNECTED;
		}
		
		// If code reaches this point then query failed
		return false;
	}
	
    /**
     * AutomationLibrary::fetchCustomerId
     * Query MAX DB to fetch the id for a route
     * 	)
     */
    public function fetchCustomerId($_customer)
    {
		if ($this->getDbStatus() != false)
		{
			if ($_customer && is_string($_customer))
			{
				$_query = preg_replace("@%t@", $_customer, AutomationLibrary::SQL_QUERY_CUSTOMER);
			
				if ($_query && is_string($_query))
				{
					$_sql_result = $this->pdoobj->getDataFromQuery($_query);
				
					if ($_sql_result && is_array($_sql_result))
					{
						if (isset($_sql_result[0]['ID']))
						{
							if ($_sql_result[0]['ID'])
							{
								return $_sql_result[0]['ID'];
							}
						}
					}
				}
			}
		} else
		{
			$this->_errors[] = AutomationLibrary::ERR_DB_NOT_CONNECTED;
		}
		
		// If code reaches this point then query failed
		return false;
	}
	
	/**
     * AutomationLibrary::dateTimeAmend($_dateStr, $_strtotime)
     * Manipulate date time by using the strtotime function
     */
    public static function dateTimeAmend($_dateStr, $_dateFmt, $_strtotime)
    {
		if ($_dateStr && is_string($_dateStr) && $_dateFmt && is_string($_dateFmt) && $_strtotime && is_string($_strtotime))
		{
			try
			{
				$_result = date($_dateFmt, strtotime($_dateStr . " $_strtotime"));
				return $_result;
			} catch (Exception $e)
			{
				return false;
			}
		}
	}

    /**
     * AutomationLibrary::fetchRateId($_customer_id, $_route_id, $_bu_id, $_trucktype_id, $_objreg_id)
     * Query MAX DB to fetch the id for a rate
     */
    public function fetchRateId($_customer_id, $_route_id, $_bu_id, $_trucktype_id, $_objreg_id)
    {
		if ($this->getDbStatus() != false)
		{
			if (intval($_customer_id) && intval($_route_id) && intval($_bu_id) && intval($_trucktype_id) && intval($_objreg_id))
			{
				$_query = preg_replace("@%ro@", $_route_id, AutomationLibrary::SQL_QUERY_RATE_TRIMMED);
				$_query = preg_replace("@%g@", $_objreg_id, $_query);
				$_query = preg_replace("@%c@", $_customer_id, $_query);
				$_query = preg_replace("@%d@", $_trucktype_id, $_query);
				$_query = preg_replace("@%b@", $_bu_id, $_query);
				
				if ($_query && is_string($_query))
				{
					$_sql_result = $this->pdoobj->getDataFromQuery($_query);
				
					if ($_sql_result && is_array($_sql_result))
					{
						if (isset($_sql_result[0]['ID']))
						{
							if ($_sql_result[0]['ID'])
							{
								return $_sql_result[0]['ID'];
							}
						}
					}
				}
			}
		} else
		{
			$this->_errors[] = AutomationLibrary::ERR_DB_NOT_CONNECTED;
		}
		
		// If code reaches this point then query failed
		return false;
	}
	
    /**
     * AutomationLibrary::getMatchingKeys($_array1, $_array2)
     * Find and return any of the array1 keys found in array2
     * $_array1 needs to be numerical indexed with the values
     * been set as the expected keys to be search in array2
     * 
     * $_array1 = array('key1', 'key2');
     * $_array2 = array('key1' => 'value1', 'key2' => 'value2');
     */
    public static function getMatchingKeys($_array1, $_array2)
    {
		$searchPatt = sprintf("@%s@", implode('|', $_array1));

		$_arr_options = preg_grep($searchPatt, array_keys($_array2));
		
		if ($_arr_options && is_array($_arr_options))
		{
			return $_arr_options;
		}
	}
	
    /**
     * AutomationLibrary::fetchDateRangeValueId($_object_reg_id, $_object_instance_id, $_type, $_extra_array = NULL)
     * Query MAX DB to fetch the id for a daterangevalue
     */
    public function fetchDateRangeValueId($_object_reg_id, $_object_instance_id, $_type, $_extra_array = NULL)
    {
		if ($this->getDbStatus() != false)
		{
			if (intval($_object_reg_id) && intval($_object_instance_id) && is_string($_type) && $_type)
			{
				$_query = preg_replace("@%g@", $_object_reg_id, AutomationLibrary::SQL_QUERY_DRV);
				$_query = preg_replace("@%r@", $_object_instance_id, $_query);
				$_query = preg_replace("@%t@", $_type, $_query);
				
				if ($_extra_array && is_array($_extra_array))
				{
					$_array_check = (array) array(
						"beginDate", "endDate"
					);
					
					$_arr_options = self::getMatchingKeys($_array_check, $_extra_array);
					
					if ($_arr_options)
					{
						$_add_query = '';
						
						foreach($_arr_options as $key1 => $value1)
						{
							if ($value1 == 'beginDate' || $value1 == 'endDate')
							{
								$_add_query = sprintf(" and (%s = \"%s\" or %s > \"%s\")", $value1, $_extra_array[$value1],$value1, $_extra_array[$value1]);
								
							} else
							{
								$_add_query = sprintf(" and %s = %s", $value1, $_extra_array[$value1]);
							}
							
							if ($_add_query && is_string($_add_query))
							{
								$_query .= $_add_query;
							}
						}
					}
				}

				if ($_query && is_string($_query))
				{
					$_sql_result = $this->pdoobj->getDataFromQuery($_query);
				
					if ($_sql_result && is_array($_sql_result))
					{
						if (isset($_sql_result[0]['ID']))
						{
							if ($_sql_result[0]['ID'])
							{
								return $_sql_result[0]['ID'];
							}
						}
					}
				}
			}
		} else
		{
			$this->_errors[] = AutomationLibrary::ERR_DB_NOT_CONNECTED;
		}
		
		// If code reaches this point then query failed
		return false;
	}

    /**
     * AutomationLibrary::CONSOLE_OUTPUT($_heading, $_description, $_type, $_query, $_data)
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
     * AutomationLibrary::getMAXURL($_mode, $_version)
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
     * AutomationLibrary::getDefaultConfigOptionsArray()
     * 
     * Fetch the default config options in an array form
     *
     * @param return: $this->_config_array_defaults
     */
    public static function getDefaultConfigOptionsArray()
    {
		return AutomationLibrary::$_config_array;
	}
	
    /**
     * AutomationLibrary::getConfigFilePath()
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
     * AutomationLibrary::mergeConfigOptions()
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
			$_result = array_merge_recursive($_config_array, AutomationLibrary::$_config_array);
			
			if (is_array($_result) && count($_result) >= 1)
			{
				return $_result;
			} else
			{
				return AutomationLibrary::$_config_array_defaults;
			}
			
		} else
		{
			return AutomationLibrary::$_config_array_defaults;
		}
	}
    
    /**
     * AutomationLibrary::addErrorRecord(&$_errArr, $_scrDir, $_errmsg, $_record, $_process)
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
     * AutomationLibrary::stringHypenFix($_value)
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
     * AutomationLibrary::getSelectedOptionValue($_using, $_value, &$this->_sessionObj)
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
     * AutomationLibrary::assertElementPresent($_using, $_value, &$this->_sessionObj, &$this->_phpunitObj)
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
		self::setDefaultConfigOptions(AutomationLibrary::$_config_array_defaults);
            
		// Check if mysql class has method to fetch its config options
		$_db_config = PullDataFromMySQLQuery::getDefaultConfigOptions();
		
		if (is_array($_db_config) && count($_db_config) > 0)
		{
			$_new_config = AutomationLibrary::mergeConfigOptions($_db_config, AutomationLibrary::$_config_array);
			
			if (is_array($_new_config) && count($_new_config) > 0)
			{
				self::setDefaultConfigOptions($_new_config);
			}
		}
	}
    
    /**
     * AutomationLibrary::takeScreenshot()
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
     * AutomationLibrary::writeExcelFile($excelFile, $excelData)
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
     * AutomationLibrary::importCSVFileIntoArray($csvFile)
     * From supplied csv file save data into multidimensional array
     *
     * @param string: $csvFile            
     * @param array: $_result            
     */
    public function importCSVFileIntoArray($csvFile)
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
	 * AutomationLibrary::exportToCSV($csvFile, $arr)
	 * From supplied csv file save data into multidimensional array
	 *
	 * @param string: $csvFile
	 * @param array: $_arr
	 */
	public function exportToCSV($csvFile, $_arr) {
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
     * AutomationLibrary::LoadJSONFile($_file)
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
     * AutomationLibrary::verifyKeysMatchInArrays($_array1, $_array2)
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
     * AutomationLibrary::verifyAndLoadConfig($_config_array)
     * Using a multidimensional passed as an argument
     * Verify all keys are present and return values for each key
     *
     * @param return: $_result   
     */	
	public static function verifyAndLoadConfig($_config_array, $_file)
	{
		
		$_json_config_data = AutomationLibrary::LoadJSONFile($_file);

		if ($_json_config_data)
		{
			
			$_result = AutomationLibrary::verifyKeysMatchInArrays($_config_array, $_json_config_data);
			
			if ($_result && is_array($_result))
			{
				return $_result;
			}
		}
		
		return false;
	}
	
	/**
     * AutomationLibrary::setDefaultConfigOptions($_config_array)
     * Using a multidimensional passed as an argument
     * Verify all keys are present and return values for each key
     *
     * @param return: $_result
     */	
	public static function setDefaultConfigOptions($_config_array)
	{
		// Verify that default keys exist
		$_result = self::verifyKeysMatchInArrays(AutomationLibrary::$_config_array_defaults, $_config_array);
		
		if (is_array($_result))
		{
			AutomationLibrary::$_config_array = $_config_array;
		}
	}
	
	// : Private Methods
	
	/**
     * AutomationLibrary::getDbStatus
     * Determine if the the pdo object has made a connection to the DB
     */
     
    private function getDbStatus()
    {
		if ($this->pdoobj !== false)
		{
			return true;
		} else
		{
			return false;
		}
	}
	
    /**
     * AutomationLibrary::initDB($_tenant, $_config_data)
     * Open persistent connection to the database
     *
     * @param string $_tenant
     * @param array $_config_data          
     * @return bool
     */
    private function initDB($_tenant, $_config_data)
    {
		$_config_file = self::getConfigFilePath();
		
		if ($_config_file && $_tenant && is_string($_tenant))
		{
			$this->pdoobj = new PullDataFromMySQLQuery($_tenant, $_config_data);
			
			if (is_object($this->pdoobj) && $this->pdoobj) {
				return TRUE
			}
		}
		
		return FALSE;
	} 
	
    // : End
}
