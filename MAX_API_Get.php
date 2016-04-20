<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes

// : End

/**
 * MAX_API_Get.php
 *
 * @package MAX_API_Get
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

/**
 * Class usage example:
 * include('MAX_API_Get.php');
 *
 * $_api = new MAX_API_Get("live");
 * $_api->setObject('Person');
 * $_api->setFilter('email like "%timber24%"');
 * $_api->runApiQuery();
 * $_data = $_api->getData();
 *
 * if (count($_api->getErrors()) > 0)
 * {
 * print_r($_api->getErrors());
 * }
 */

/**
 * DEV NOTES:
 *
 * Detect bad user and/or password when making curl call
 */
class MAX_API_Get
{
    
    // : Constants
    const INI_FILE = "api_data.ini";

    const LIVE_URL = "https://login.max.bwtsgroup.com";

    const TEST_URL = "http://max.mobilize.biz";

    const API_URL = "/api_request/Data/get?objectRegistry=";

    const ENV_VAR = "BWT_CONFIG_PATH";

    const DS = DIRECTORY_SEPARATOR;

    const OBJREG = "objectRegistry";

    const LOG_STR = PHP_EOL . "STEP DETAIL: %s" . PHP_EOL . "LOG HEADER: %s" . PHP_EOL . "LOG DETAIL: %s" . PHP_EOL . "FUNCTION: %s" . PHP_EOL . "LINE: %s";

    const ERR_STR = PHP_EOL . "STEP DETAIL: %s" . PHP_EOL . "ERROR HEADER: %s" . PHP_EOL . "ERROR DETAIL: %s" . PHP_EOL . "FUNCTION: %s" . PHP_EOL . "LINE: %s";

    const ERR_NO_FILTER_OR_OBJECT = 'No API filter or object has been set. Please use methods setFilter and setObject to set them';

    const ERR_CONFIG_NOT_COMPLETE = 'One or more config is not set. FILTER: %s, OBJECT: %s, MAXURL: %s, MAXUSRPWD: %s';

    const ERR_NO_RESULT = 'No result returned from curl call to MAX API. FILTER: %s, OBJECT: %s';

    const ERR_FAILED_TO_EXTRACT_DATA_FROM_HTML = 'Failed to extract data from HTML. No result returned.';
    // : End - Constants
    
    // : Variables
    
    // Define array containing all valid objectRegistry entries that can be used to get data using the MAX Get API
    protected $_data = array();

    protected $_sqlQueryString;

    protected $_xmlResponseString;

    protected $_htmlDataString;

    protected $_apiObject;

    protected $_apiFilter;

    protected $_maxurl;

    protected $_apiuserpwd;

    protected $_errors = array();

    protected $_logs = array();

    protected $_localObjects = array();

    protected $_latestObjects = array();

    protected $_maxObjects = array();
    // : End
    
    // : Getters
    /**
     * MAX_API_Get::getErrors()
     * Return logged errors if any else return FALSE
     *
     * @return mixed
     */
    public function getErrors()
    {
        if ($this->_errors) {
            return $this->_errors;
        }
        
        return FALSE;
    }

    /**
     * MAX_API_Get::getLogs()
     * Return logs if any else return FALSE
     *
     * @return array
     */
    public function getLogs()
    {
        if ($this->_logs) {
            return $this->_logs;
        }
        
        return FALSE;
    }

    /**
     * MAX_API_Get::getData()
     * Get data returned from runApiQuery
     */
    public function getData()
    {
        if ($this->_data) {
            if (is_array($this->_data) || (is_string($this->_data) && $this->_data == 'No rows')) {
                return $this->_data;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * MAX_API_Get::getLastStatus()
     * Get status reponse given in XML file returned in result
     */
    public function getLastStatus()
    {
        // : Some code goes here
    }

    /**
     * MAX_API_Get::getObjects()
     * Get list of available objectRegistry objects that can be used to get data
     */
    public function getObjects()
    {
        try {
            $_result = implode("," . PHP_EOL, $this->_maxObjects);
        } catch (Exception $e) {
            return FALSE;
        }
        return $_result;
    }
    
    // : Setters
    /**
     * MAX_API_Get::clearResults()
     * Reset data and query
     */
    public function clearResults()
    {
        $this->_data = array();
        $this->_sqlQueryString = "";
        $this->_xmlResponseString = "";
        $this->_htmlDataString = "";
        $this->_apiObject = "";
        $this->_apiFilter = "";
    }

    /**
     * MAX_API_Get::setFilter()
     * Set filter string for the API query
     */
    public function setFilter($_filterStr)
    {
        $this->_apiFilter = $_filterStr;
    }

    /**
     * MAX_API_Get::setObject()
     * Set the objectRegistry object for the API query
     */
    public function setObject($_objectStr)
    {
        $_findMatch = preg_grep("/^$_objectStr$/i", $this->_maxObjects);
        if ($_findMatch) {
            $this->_apiObject = $_objectStr;
        } else {
            return FALSE;
        }
    }
    
    // : Public Functions
    
    /**
     * MAX_API_Get::dd($_var)
     * Die and var_dump
     */
    public static function dd($_var)
    {
        die(var_dump($_var));
    }

    /**
     * MAX_API_Get::runApiQuery()
     * Run the API query
     *
     * @return
     *
     */
    public function runApiQuery()
    {
        try {
            if ($this->_apiObject && $this->_apiFilter) {
                
                $_result = $this->splitResultIntoDataArray($this->maxApiGetData());
                
                if ($_result) {
                    $this->_xmlResponseString = $_result['xml'];
                    $this->_htmlDataString = $_result['html'];
                    
                    $this->_data = $this->extractDataFromHTML($this->_htmlDataString);
                    return TRUE;
                } else {
                    return FALSE;
                }
            } else {
                $this->addError('Run API Query', 'Required config not set: ', sprintf(self::ERR_NO_FILTER_OR_OBJECT, strval($this->_apiObject), strval($this->_apiFilter)), __FUNCTION__, __LINE__);
                return FALSE;
            }
        } catch (Exception $e) {
            $this->addError('Run API Query', 'Caught Exception: ', $e->getMessage(), __FUNCTION__, __LINE__);
            return FALSE;
        }
    }
    
    // : End - Public Functions
    
    // : Magic
    
    /**
     * MAX_API_Get::__construct()
     * Class constructor
     */
    public function __construct($_mode)
    {
        try {
            
            // Check if environment variable is set else fail
            if (getenv(self::ENV_VAR)) {
                
                $_ini_path = getenv(self::ENV_VAR);
                
                $ini = $_ini_path . self::DS . self::INI_FILE;
                
                if (is_file($ini) === FALSE) {
                    $this->addError('Load INI File', 'INI File Not Found: ', $ini, __FUNCTION__, __LINE__);
                    return FALSE;
                }
                
                $data = parse_ini_file($ini);
                
                if ((array_key_exists("apiuserpwd", $data) && $data["apiuserpwd"]) && (array_key_exists("apiusertestpwd", $data) && $data["apiusertestpwd"])) {
                    
                    switch ($_mode) {
                        case "live":
                            {
                                $this->_maxurl = self::LIVE_URL;
                                $this->_apiuserpwd = $data['apiuserpwd'];
                                break;
                            }
                        case "test":
                        default:
                            {
                                $this->_maxurl = self::TEST_URL;
                                $this->_apiuserpwd = $data['apiusertestpwd'];
                                break;
                            }
                    }
                } else {
                    $this->addError('Validate INI File Data', 'Required fields not found', 'Please check that apiuserpwd key=value is present in file: ' . $ini, __FUNCTION__, __LINE__);
                    return FALSE;
                }
            } else {
                $this->addError('object construct', '__construct failed with an Exception:', 'Environment variable expected but not found: ' . self::ENV_VAR, __FUNCTION__, __LINE__);
                return FALSE;
            }
        } catch (Exception $e) {
            $this->addError('object construct', '__construct failed with an Exception:', $e->getMessage(), __FUNCTION__, __LINE__);
            return FALSE;
        }
        // If code reaches this point then code processed successfully
        return TRUE;
    }
    
    // : End
    
    // : Private Functions
    /**
     * MAX_API_Get::maxApiGetData($_url)
     * Get data from MAX using using MAX API HTTP GET request
     *
     * @return mixed
     */
    public function maxApiGetData()
    {
        $_result = array();
        
        if ($this->_apiFilter && $this->_apiObject && $this->_maxurl && $this->_apiuserpwd) {
            try {
                // Build url string to use to run the API request
                $_url = ($this->_maxurl . self::API_URL . urlencode($this->_apiObject) . "&filter=" . urlencode($this->_apiFilter));
                $this->addLogEntry(__FUNCTION__, 'Print out curl url', $_url, __FUNCTION__, __LINE__);
                
                $ch = curl_init();
                
                curl_setopt($ch, CURLOPT_URL, $_url);
                curl_setopt($ch, CURLOPT_USERPWD, $this->_apiuserpwd);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                $output = curl_exec($ch);
                
                curl_close($ch);
            } catch (Exception $e) {
                $this->addError('CURL fetch data', 'cURL failed to get data with Exception: ', $e->getMessage(), __FUNCTION__, __LINE__);
                return FALSE;
            }
            $this->addLogEntry(__FUNCTION__, 'Print out curl response', $output, __FUNCTION__, __LINE__);
            return $output;
        } else {
            $this->addError('CURL fetch data', 'cURL failed to get data with Exception: ', sprintf(self::ERR_CONFIG_NOT_COMPLETE, strval($this->_apiFilter), strval($this->_apiObject), strval($this->_maxurl), strval($this->_apiuserpwd)), __FUNCTION__, __LINE__);
        }
        
        return FALSE;
    }

    /**
     * MAX_API_Get::fetchObjectRegistryObjects()
     * Fetch Object Registry objects using MAX API
     *
     * @return bool
     */
    private function fetchObjectRegistryObjects()
    {}

    /**
     * MAX_API_Get::loadLocalObjectRegistryData()
     * Load local file containing object registry objects
     *
     * @return bool
     */
    private function loadLocalObjectRegistryData()
    {}

    /**
     * MAX_API_Get::diffObjectRegistryObjects()
     * Check if there is anything difference between the
     * newly fetched data and the locally stored data
     *
     * @return bool
     */
    private function diffObjectRegistryObjects()
    {}

    /**
     * MAX_API_Get::updateLocalObjectRegistryData()
     * Save newly fetched object registry objects data to
     * local file
     *
     * @return bool
     */
    private function updateLocalObjectRegistryData()
    {}

    /**
     * MAX_API_Get::updateLocalObjectRegistryData()
     * Set the object registry objects array to the newly fetched data
     *
     * @return bool
     */
    private function setObjectRegistryObjects()
    {}

    /**
     * MAX_API_Get::splitResultIntoDataArray()
     * Clean HTML string and extract data into an array
     *
     * @param string $_htmlResponse            
     * @return mixed
     */
    private function splitResultIntoDataArray($_htmlResponse)
    {
        try {
            // : Prepare variables
            $_httpUrlData = explode("\n", $_htmlResponse);
            $_htmlRecPos = (array) array();
            $xmlStartLine = (int) 0;
            $xmlEndLine = (int) 0;
            $xmlDef = (int) 0;
            $_fieldCount = (int) 0;
            $_xmlData = (array) array();
            $_htmlData = (array) array();
            $_result = (array) array();
            $_htmlNoRows = (boolean) FALSE;
            // : End
            
            // self::dd($_httpUrlData);
            
            // : Determine if response is good and expected else terminate with error
            if (is_string($_htmlResponse) && $_htmlResponse && count($_httpUrlData) > 0) {
                
                // : Determine the amount fields per record
                foreach ($_httpUrlData as $_key => $_value) {
                    if (strpos(strtolower($_value), 'id:')) {
                        $rowcount = 0;
                        
                        while (strlen($_httpUrlData[$_key + $rowcount]) != 0 && strpos($_httpUrlData[$_key + $rowcount], ":")) {
                            $rowcount ++;
                        }
                        
                        if ($rowcount > 0 && $rowcount) {
                            $_fieldCount = $rowcount;
                        }
                    } else 
                        if ($_value == 'No rows') {
                            $_htmlNoRows = TRUE;
                            break;
                        }
                    
                    break;
                }
                
                // : Detect line numbers where each section of data is situated
                foreach ($_httpUrlData as $_key => $_value) {
                    
                    if ($_value && strpos(strtolower($_value), 'id:')) {
                        $_htmlRecPos[] = $_key;
                    } else 
                        if (strpos($_value, '<response>') !== FALSE) {
                            $xmlStartLine = $_key;
                        } else 
                            if (strpos($_value, '</response>') !== FALSE) {
                                $xmlEndLine = $_key;
                            } else 
                                if (strpos($_value, '<?xml version="1.0" encoding="UTF-8"?>') !== FALSE) {
                                    $xmlDef = $_key;
                                }
                }
                // : End
                
                // : Construct the HTML Data into an array
                
                if (is_array($_htmlRecPos) && count($_htmlRecPos) > 0 && $_htmlNoRows == FALSE) {
                    
                    foreach ($_htmlRecPos as $_key => $_value) {
                        
                        if ($_value != NULL) {
                            for ($a = 0; $a <= $_fieldCount; $a ++) {
                                $_htmlData[$_key][] = $_httpUrlData[$_value + $a];
                            }
                        }
                    }
                } else 
                    if ($_htmlNoRows) {
                        $_htmlData['html'][] = 'No rows';
                    }
                // : End
                
                // : Construct the XML Data into an array
                if ($xmlEndLine != FALSE && $xmlStartLine != FALSE) {
                    for ($x = $xmlStartLine; $x <= $xmlEndLine; $x ++) {
                        $_xmlData[] = $_httpUrlData[$x];
                    }
                }
                // : End
                
                $_result["html"] = $_htmlData;
                $_result["xml"] = $_xmlData;
                
                return $_result;
            } else {
                $this->addError('Check HTML Response', 'Not a valid HTTP response: ', $_htmlResponse, __FUNCTION__, __LINE__);
            }
            // : End
        } catch (Exception $e) {
            $this->addError('Split Result Into Data Array', 'Caught Exception: ', $e->getMessage(), __FUNCTION__, __LINE__);
        }
        
        return FALSE;
    }

    /**
     * MAX_API_Get::addError($_step, $_errTitle, $_errDetail, $_func = NULL, $_line = NULL)
     * Add a new error to the class protected property _errors
     *
     * @param string $_step            
     * @param string $_errTitle            
     * @param string $_errDetail            
     * @param string $_func            
     * @param string $_line            
     * @return bool
     *
     */
    private function addError($_step, $_errTitle, $_errDetail, $_func = NULL, $_line = NULL)
    {
        if (is_string($_step) && is_string($_errTitle) && $_step && $_errTitle && $_errDetail) {
            
            // Build error message string to store as new indice in errors array
            $_errMsg = sprintf(self::ERR_STR, strval($_step), strval($_errTitle), strval($_errDetail), strval($_func), strval($_line));
            
            if ($_errMsg && is_string($_errMsg)) {
                
                $this->_errors[] = $_errMsg;
                return TRUE;
            } else {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * MAX_API_Get::addLogEntry($_step, $_logTitle, $_logDetail, $_func = NULL, $_line = NULL)
     * Add a new log entry to the class protected property _logs
     *
     * @param string $_step            
     * @param string $_logTitle            
     * @param string $_logDetail            
     * @param string $_func            
     * @param string $_line            
     * @return bool
     *
     */
    private function addLogEntry($_step, $_logTitle, $_logDetail, $_func = NULL, $_line = NULL)
    {
        if (is_string($_step) && is_string($_logTitle) && $_step && $_logTitle && $_logDetail) {
            
            // Build log message string to store as new indice in logs array
            $_logMsg = sprintf(self::LOG_STR, strval($_step), strval($_logTitle), strval($_logDetail), strval($_func), strval($_line));
            
            if ($_logMsg && is_string($_logMsg)) {
                
                $this->_logs[] = $_logMsg;
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * MAX_API_Get::getDataFromHTML()
     * Clean HTML string and extract data into an array
     *
     * @param array $_htmlData            
     * @return mixed
     */
    private function extractDataFromHTML($_htmlData)
    {
        try {
            $_result = (array) array();
            $_testForNoRows = array_values($_htmlData);
            
            if ($_testForNoRows && is_array($_testForNoRows)) {
                
                if ($_testForNoRows[0] == "No rows") {
                    $_testForNoRows = TRUE;
                } else {
                    $_testForNoRows = FALSE;
                }
            }
            
            if ($_testForNoRows == FALSE) {
                
                foreach ($_htmlData as $_key => $_html) {
                    
                    foreach ($_html as $_index => $_value) {
                        
                        // Set variable string values to empty for each loop
                        $_htmlKey = "";
                        $_htmlValue = "";
                        
                        // Seperate key from value
                        
                        // Split is working properly
                        $_split = preg_split("/:\s/", $_html[$_index]);
                        
                        if (count($_split) > 1) {
                            
                            // Clean up spaces in the value
                            preg_match("/([A-Za-z0-9].*$)/", $_split[1], $_cleanStr);
                            
                            if (count($_cleanStr) > 1) {
                                
                                // Successful find which cleans out the spaces from the value
                                $_htmlValue = $_cleanStr[1];
                            } else 
                                if (count($_cleanStr)) {
                                    
                                    // No spaces found return value
                                    $_htmlValue = $_cleanStr[0];
                                }
                            
                            preg_match("/\s([a-zA-Z0-9].*$)/", $_split[0], $_cleanStr);
                            
                            if (count($_cleanStr) > 1) {
                                
                                // Spaces found and spaceless values returned
                                $_htmlKey = $_cleanStr[1];
                            }
                        }
                        
                        if (($_htmlKey && $_htmlValue) || ($_htmlKey && ! $_htmlValue)) {
                            
                            $_result[$_key][$_htmlKey] = $_htmlValue;
                        }
                    }
                }
            } else {
                $this->addError(__FUNCTION__, 'Extract data from HTML', 'No rows', __FUNCTION__, __LINE__);
                $_result = "No rows";
            }
            
            if ($_result) {
                return $_result;
            } else {
                $this->addError(__FUNCTION__, 'No results', '#' . strval(count($_result)), __FUNCTION__, __LINE__);
                return "No results";
            }
        } catch (Exception $e) {
            $this->addError(__FUNCTION__, 'Caught Exception: ', $e->getMessage(), __FUNCTION__, __LINE__);
            return FALSE;
        }
        
        return FALSE;
    }

    /**
     * MAX_API_Get::LoadJSONFile($_file)
     * Load JSON data file
     *
     * @return bool
     */
    private function LoadJSONFile($_file)
    {
        // Default _result to FALSE
        $_result = false;
        
        try {
            
            if (file_exists($_file)) {
                $_json_file = file_get_contents($_file);
                
                if ($_json_file) {
                    $_json_data = json_decode($_json_file, true);
                    
                    if ($_json_data && is_array($_json_data)) {
                        $_result = $_json_data;
                    }
                }
            }
        } catch (Exception $e) {
            echo "Caught exception: ", $e->getMessage(), "\n";
            return FALSE;
        }
        
        return $_result;
    }

    /**
     * MAX_API_Get::SaveJSONFile($_file)
     * Save JSON data to file in JSON format
     *
     * @param string $_file            
     * @param array $_json_array            
     * @return bool
     */
    private function SaveJSONFile($_file, $_json_array)
    {
        // Default _result to FALSE
        $_result = FALSE;
        
        try {
            
            if (is_array($_json_array) && $_json_array && is_string($_file) && $_file) {
                $_json_data = json_encode($_json_array);
                
                if (is_string($_json_data) && $_json_data) {
                    if (file_put_contents($_file, $_json_data) !== FALSE) {
                        return TRUE;
                    } else {
                        $this->addError(__FUNCTION__, 'Failed attempt to save file using file_put_contents method', strval($_file), __FUNCTION__, __LINE__);
                    }
                } else {}
            } else {
                $this->addError(__FUNCTION__, 'Argument: JSON is empty or not an array, OR, file is empty or not a string', strval($_file), __FUNCTION__, __LINE__);
            }
        } catch (Exception $e) {
            $this->addError(__FUNCTION__, 'Attempt to save JSON file failed', $e->getMessage(), __FUNCTION__, __LINE__);
        }
        
        return FALSE;
    }
    // : End - Private Functions
}
