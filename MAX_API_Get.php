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
 * @copyright 2016 onwards Barloworld Transport (Pty) Ltd
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
 * - Detect bad user and/or password when making curl call
 * - Add function to convert a multidimensional array into a string representation -> Handy for debug info
 */
class MAX_API_Get
{
    
    // : Constants
    const CONFIG_FILE = "bwt-config.json";

    const LIVE_URL = "https://login.max.bwtsgroup.com";

    const TEST_URL = "http://max.mobilize.biz";

    const API_URL = "/api_request/Data/get?objectRegistry=";

    const ENV_VAR = "BWT_CONFIG_PATH";

    const DS = DIRECTORY_SEPARATOR;

    const OBJREG = "objectregistry";
    
    const OBJREG_API_QUERY = 'handle like "%s"';

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

    protected $_pulledObjects = array();

    protected $_maxObjects = array();
    
    protected $_debugData = array();
    
    protected $_objRegFile = FALSE;
    // : End
    
    // : Getters
    /**
     * MAX_API_Get::getDebugData()
     * Return debug data array
     *
     * @return mixed
     */
    public function getDebugData()
    {
        if ($this->_debugData) {
            return $this->_debugData;
        }
    
        return FALSE;
    }    

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
    
    /**
     * MAX_API_Get::getMaxUrl()
     * Get MAX URL fetched from the config file
     * 
     * @return mixed
     */
    public function getMaxUrl()
    {
        if (is_string($this->_maxurl) && $this->_maxurl) {
            return $this->_maxurl;
        }
        
        return FALSE;
    }
    
    /**
     * MAX_API_Get::getObjRegFilePath()
     * Get object registry objects JSON file path
     * Contains object registry objects fetched from MAX
     *
     * @return mixed
     */
    public function getObjRegFilePath()
    {
        if (is_string($this->_objRegFile) && $this->_objRegFile) {
            return $this->_objRegFile;
        }
    
        return FALSE;
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
            if ($this->_apiObject && is_string($this->_apiFilter)) {
                
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

    /**
     * MAX_API_Get::extractEnvFromStr($_subject)
     * Extract the env name from a formatted string: ENV:ENV_NAME
     *
     * @param string $_subject
     * @return mixed
     */
    public function extractEnvFromStr($_subject)
    {
        $_results = (array) array();

        preg_match('/^ENV:.*/i', $_subject, $_results);
        
        if (is_array($_results) && $_results) {
            $_results = preg_split('/^ENV:/', -1, PREG_SPLIT_NO_EMPTY);
            
            if (count($_results) === 1 && is_array($_results)) {
                return $_results[0];
            }
        }
        
        return FALSE;
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
                
                $_config = (array) array();
                $_config_path = getenv(self::ENV_VAR);
                
                $_config_file = $_config_path . self::DS . self::CONFIG_FILE;
                
                if (is_file($_config_file) === FALSE) {
                    $this->addError('Load JSON File', 'JSON File Not Found: ', $_config_file, __FUNCTION__, __LINE__);
                    return FALSE;
                }
                
                $this->addLogEntry(__CLASS__, 'Attempt to Load JSON file', $_config_file, __FUNCTION__, __LINE__);
                $_config = $this->loadJSONFile($_config_file);
                
                $this->addLogEntry(__CLASS__, 'Loaded JSON file', $_config, __FUNCTION__, __LINE__);
                
                if ($_config && is_array($_config)) {
                        
                        $this->addLogEntry(__CLASS__, 'Loaded JSON file and decoded data', $_config, __FUNCTION__, __LINE__);

                        if (isset($_config['config']['dataManager']['default_mode']) && isset($_config['config']['dataManager']['tenant']) && isset($_config['config']['dataManager']['curl']['apiuserpwd']) && isset($_config['config']['dataManager']['curl']['objregpath']) && isset($_config['config']['dataManager']['curl']['objregfile'])) {
                            
                            $_mode = $_config['config']['dataManager']['tenant'];
                            $this->_apiuserpwd = $_config['config']['dataManager']['curl']['apiuserpwd'];
                            
                            // Check if ENV:ENV_NAME format string provided for PATH
                            $_env_path = $this->extractEnvFromStr($_config['config']['dataManager']['curl']['objregpath']);
                            
                            // If ENV provided then use it else just use the string found in the config data
                            $_objregpath = $_env_path ? $_env_path : $_config['config']['dataManager']['curl']['objregpath'];
                            
                            // Store data into debug data property to report on
                            $this->_debugData = $_objregpath;
                            
                            $_objregfile = $_config['config']['dataManager']['curl']['objregfile'];
                            
                            $this->_objRegFile = $_objregpath . self::DS . $_objregfile;
                            
                            switch ($_mode) {
                                case "live": {
                                    $this->_maxurl = self::LIVE_URL;
                                    break;
                                }
                                case "test":
                                default: {
                                    $this->_maxurl = self::TEST_URL;
                                }
                            }
                            
                            $this->fetchObjectRegistryObjects();
                            
                        } else {
                            $this->addError(__CLASS__, 'Required fields not found in json config file. Run script from cmd line for help to generate a config file', $_config_file, __FUNCTION__, __LINE__);
                        }
                        
                }
                
            } else {
                $this->addError(__CLASS__, 'Environment variable expected but not found: ', self::ENV_VAR, __FUNCTION__, __LINE__);
            }
        } catch (Exception $e) {
            $this->addError(__CLASS__, '__construct failed with an Exception:', $e->getMessage(), __FUNCTION__, __LINE__);
        }
    }
    
    // : End
    
    // : Private Functions
    /**
     * MAX_API_Get::maxApiGetData()
     * Get data from MAX using using MAX API HTTP GET request
     *
     * @return mixed
     */
    public function maxApiGetData()
    {
        $_result = array();
        
        if (is_string($this->_apiFilter) && $this->_apiObject && $this->_maxurl && $this->_apiuserpwd) {
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
                $this->addError(__CURL__, 'cURL failed to get data with Exception: ', $e->getMessage(), __FUNCTION__, __LINE__);
                return FALSE;
            }
            $this->addLogEntry(__CURL__, 'Print out curl response', $output, __FUNCTION__, __LINE__);
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
    {
        $this->_apiObject = self::OBJREG;
        $this->setFilter('');
        $this->runApiQuery();
        $_results = $this->getData();
        $this->_debugData = $_results;
    }

    /**
     * MAX_API_Get::loadLocalObjectRegistryData()
     * Load local file containing object registry objects
     *
     * @return bool
     */
    private function loadLocalObjectRegistryData()
    {
        if ($this->_objRegFile && is_string($this->_objRegFile)) {
            
            if (file_exists($this->_objRegFile)) {
                
                $_results = $this->loadJSONFile($this->_objRegFile);
                
                if ($_results && is_array($_results)) {
                    
                    if (isset($_results['max']['objects'])) {
                        
                        $this->_localObjects = $_results;
                        return TRUE;
                    } else {
                        $this->addError(__CLASS__, 'Objects JSON data validation failed. Expected key not found in JSON data: ', 'max.objects', __FUNCTION__, __LINE__);
                    }
                } else {
                    $this->addError(__CLASS__, 'No JSON found on attempt to load objects JSON data file ', $this->_objRegFile, __FUNCTION__, __LINE__);
                }
            }
        }
        
        return FALSE;
    }

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
     * MAX_API_Get::extractDataFromHTML()
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
     * MAX_API_Get::loadJSONFile($_file)
     * Load JSON data file
     *
     * @return bool
     */
    private function loadJSONFile($_file)
    {
        // Default _result to FALSE
        $_result = false;
        
        try {
            
            if (file_exists($_file)) {
                $_json_file = file_get_contents($_file);
                
                if ($_json_file && is_string($_json_file)) {
                    $_json_data = json_decode($_json_file, true);
                    
                    if ($_json_data && is_array($_json_data)) {
                        $_result = $_json_data;
                        return $_result;
                    } else {
                        $this->addError(__CLASS__, 'No JSON data found: ', $_json_file, __FUNCTION__, __LINE__);
                        // Debug
                        $this->_debugData = $_json_data;
                    }
                } else {
                    $this->addError(__CLASS__, 'No contents in the JSON file: ', $_json_file, __FUNCTION__, __LINE__);
                }
            } else {
                $this->addError(__CLASS__, 'File not found: ', $_json_file, __FUNCTION__, __LINE__);
            }
        } catch (Exception $e) {
            $this->addError(__CLASS__, 'Caught Exception: ', $e->getMessage(), __FUNCTION__, __LINE__);
        }
        
        return FALSE;
    }

    /**
     * MAX_API_Get::saveJSONFile($_file)
     * Save JSON data to file in JSON format
     *
     * @param string $_file            
     * @param array $_json_array            
     * @return bool
     */
    private function saveJSONFile($_file, $_json_array)
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
    
    /**
     * MAX_API_Get::envExists($_env_name)
     * Check if supplied env name exists
     *
     * @param string $_env_name
     * @return bool
     */
    private function envExists($_env_name)
    {
        try {
            $_result = getenv($_env_name);
            
            if ($_result || is_string($_result)) {
                return $_result;
            } else {
                $this->addError(__CLASS__, 'Environment variable not found: ', $_env_name, __FUNCTION__, __LINE__);
            }
            
        } catch (Exception $e) {
            $this->addError(__CLASS__, 'Attempt to check if environment variable exists failed with error: ', $e->getMessage(), __FUNCTION__, __LINE__);
        }
        
        return FALSE;
    }
    // : End - Private Functions
}
