<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes
// : End

/**
 * MAX_Routes_Rates.php
 *
 * @package MAX_Routes_Rates
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
class maxRoutesRates
{
    
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
    // : End - Constants
    
    // : Variables
    public $_tmp;

    protected $_maxurl;

    protected $_autoLibObj;

    protected $_errors = array();
    // : End
    
    // : Magic Functions
    /**
     * MAX_Routes_Rates::__construct
     * Class constructor
     */
    public function __construct(&$_autoObj, $_maxurl)
    {
        if (is_object($_autoObj) && is_string($_maxurl)) {
            
            // Save referenced automation library object containing session and phpunit objects
            $this->_autoLibObj = $_autoObj;
            $this->_maxurl = $_maxurl;
        }
    }

    /**
     * MAX_Routes_Rates::__destruct
     * Class destructor
     */
    public function __destruct()
    {
        unset($this);
    }
    
    // : End - Magic Functions
    
    // : Public Functions
    /**
     * MAX_Routes_Rates::maxLogin
     * Log into MAX
     */
    public function getLastError()
    {
        $_result = "";
        end($this->_errors);
        $_result = $this->_errors[key($this->_errors)];
        reset($this->_errors);
        return $_result;
    }
    
    public function getRouteName($_locationFrom, $_locationTo) {
        // Concatenate string for route name
        $_routeName = $_locationFrom . " TO " . $_locationTo;
    }

    /**
     * MAX_Routes_Rates::maxRouteCreate
     * Log into MAX
     */
    public function maxRouteCreate($_bu, $_locationFrom, $_locationTo, $_expectedKms, $_version = automationLibrary::DEFAULT_MAX_VERSION)
    {
        try {
            // : Log into MAX
            
            switch (intval($_version)) {
                case 2:
                    {
                        try {
                            $_routeName = getRouteName($_locationFrom, $_locationTo);
                            
                            $session = $this->_autoLibObj->_sessionObj;
                            $this->_autoLibObj->_sessionObj->open($this->_maxurl . automationLibrary::URL_LOCATION_ROUTE);
                            
                            // Wait for element text Route Ward
                            $e = $this->_autoLibObj->_wObj->until(function ($session)
                            {
                                return $session->element("xpath", "//*[contains(text(),'Route Ward')]");
                            });
                            
                            $this->_autoLibObj->_sessionObj->assertElementPresent("css selector", "div.toolbar-cell-create");
                            $this->_autoLibObj->_sessionObj->element("css selector", "div.toolbar-cell-create")->click();
                            
                            $e = $this->_autoLibObj->_wObj->until(function ($session)
                            {
                                return $session->element("xpath", "//*[contains(text(),'Capture the Business Unit')]");
                            });
                            
                            $this->_autoLibObj->_sessionObj->assertElementPresent("xpath", "//*[@id='udo_Rates[0][businessUnit_id]']");
                            $this->_autoLibObj->_sessionObj->assertElementPresent("css selector", "input[name=save][type=submit]");
                            
                            try {
                                $this->_autoLibObj->_sessionObj->element("xpath", "//*[@id='udo_Rates[0][businessUnit_id]']/option[text()='$_bu']")->click();
                            } catch (PHPWebDriver_NoSuchElementWebDriverError $e) {
                                throw new Exception("ERROR: Could not find the location from on the create route page" . PHP_EOL . $e->getMessage());
                            }
                            
                            $this->_autoLibObj->_sessionObj->element("css selector", "input[name=save][type=submit]")->click();
                            
                            // Wait for element Page Heading
                            $e = $this->_autoLibObj->_wObj->until(function ($session)
                            {
                                return $session->element("xpath", "//*[@name='udo_Route[0][locationFrom_id]']");
                            });
                            
                            switch ($_bu) {
                                case "Timber 24":
                                    {
                                        // : Assert all elements on page
                                        $this->assertElementPresent("xpath", "//*[@name='udo_Route[0][locationTo_id]']");
                                        $this->assertElementPresent("xpath", "//*[@name='udo_Route[0][expectedKms]']");
                                        $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                        // : End
                                        
                                        try {
                                            $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][locationFrom_id]']/option[text()='" . $_locationFrom . "']")->click();
                                        } catch (PHPWebDriver_NoSuchElementWebDriverError $e) {
                                            throw new Exception("ERROR: Could not find the location from on the create route page" . PHP_EOL . $e->getMessage());
                                        }
                                        
                                        $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][locationTo_id]']/option[text()='" . $_locationTo . "']")->click();
                                        if ($_dataset["expected kms"]["value"] != FALSE) {
                                            $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][expectedKms]']")->sendKeys($_expectedKms);
                                        }
                                        break;
                                    }
                                
                                default:
                                    {
                                        // : Assert all elements on page
                                        $this->_autoLibObj->_sessionObj->assertElementPresent("xpath", "//*[@name='udo_Route[0][locationTo_id]']");
                                        $this->_autoLibObj->_sessionObj->assertElementPresent("xpath", "//*[@name='udo_Route[0][expectedKms]']");
                                        $this->_autoLibObj->_sessionObj->assertElementPresent("xpath", "//*[@name='udo_Route[0][duration]']");
                                        $this->_autoLibObj->_sessionObj->assertElementPresent("css selector", "input[type=submit][name=save]");
                                        // : End
                                        
                                        try {
                                            $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][locationFrom_id]']/option[text()='$_locationFrom']")->click();
                                        } catch (PHPWebDriver_NoSuchElementWebDriverError $e) {
                                            throw new Exception("ERROR: Could not find the location from on the create route page" . PHP_EOL . $e->getMessage());
                                        }
                                        
                                        $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][locationTo_id]']/option[text()='$_locationTo']")->click();
                                        if ($_expectedKms != FALSE) {
                                            $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][expectedKms]']")->sendKeys($_expectedKms);
                                            // Calculate duration from kms value at 60K/H
                                            $duration = strval(number_format((floatval($_expectedKms) / 80) * 60, 0, "", ""));
                                            $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][duration]']")->sendKeys($duration);
                                        }
                                        break;
                                    }
                            }
                            
                            $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                            
                            // Wait for element text Route Ward
                            $e = $this->_autoLibObj->_wObj->until(function ($session)
                            {
                                return $session->element("xpath", "//*[contains(text(),'Route Ward')]");
                            });
                        } catch (Exception $e) {
                            
                            $this->_autoLib->addErrorRecord($this->_error, $this->_scrDir, $e->getMessage(), $this->lastRecord . ". Object data that failed: Route", "Create Route");
                        }
                        
                        break;
                    }
                case 3:
                    {
                        break;
                    }
            }
        } catch (Exception $e) {
            // Store error message into static array
            $this->_errors[] = $e->getMessage();
            return FALSE;
        }
        return TRUE;
    }
    
    // : End - Public Functions
    
    // : Private Functions
    // : End - Private Functions
}

