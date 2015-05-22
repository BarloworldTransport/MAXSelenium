<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes

// : End

/**
 * MAX_LoginLogout.php
 *
 * @package MAX_LoginLogout
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
class maxLoginLogout
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
     * MAX_LoginLogout::__construct
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
     * MAX_LoginLogout::__destruct
     * Class destructor
     */
     public function __destruct() {
        unset($this);
     }
    
     // : End - Magic Functions
     
     // : Public Functions
    /**
     * MAX_LoginLogout::maxLogin
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

    /**
     * MAX_LoginLogout::maxLogin
     * Log into MAX
     */
    public function maxLogin($_uname, $_pwd, $_welcome, $_version = automationLibrary::DEFAULT_MAX_VERSION)
    {
        try {
            // : Log into MAX
            switch (intval($_version)) {
                case 2:
                    {
                        // Load MAX home page
                        $session = $this->_autoLibObj->_sessionObj;
                        $this->_autoLibObj->_sessionObj->open($this->_maxurl);
                        
                        // : Wait for page to load and for elements to be present on page
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element('css selector', "#contentFrame");
                        });
                        
                        $iframe = $this->_autoLibObj->_sessionObj->element('css selector', '#contentFrame');
                        $this->_autoLibObj->_sessionObj->switch_to_frame($iframe);
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element('css selector', 'input[id=identification]');
                        });
                        // : End
                        
                        // : Assert element present
                        $this->_autoLibObj->assertElementPresent('css selector', 'input[id=identification]');
                        $this->_autoLibObj->assertElementPresent('css selector', 'input[id=password]');
                        $this->_autoLibObj->assertElementPresent('css selector', 'input[name=submit][type=submit]');
                        // : End
                        
                        // Send keys to input text box
                        $e = $this->_autoLibObj->_sessionObj->element('css selector', 'input[id=identification]')->sendKeys($_uname);
                        // Send keys to input text box
                        $e = $this->_autoLibObj->_sessionObj->element('css selector', 'input[id=password]')->sendKeys($_pwd);
                        
                        // Click login button
                        $this->_autoLibObj->_sessionObj->element('css selector', 'input[name=submit][type=submit]')->click();
                        // Switch out of frame
                        $this->_autoLibObj->_sessionObj->switch_to_frame();
                        
                        // : Wait for page to load and for elements to be present on page
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element('css selector', "#contentFrame");
                        });
                        $iframe = $this->_autoLibObj->_sessionObj->element('css selector', '#contentFrame');
                        $this->_autoLibObj->_sessionObj->switch_to_frame($iframe);
                        
                        $this->_tmp = $_welcome;
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//*[text()='" . $this->_tmp . "']");
                        });
                        $this->_autoLibObj->assertElementPresent("xpath", "//*[text()='" . $_welcome . "']");
                        // Switch out of frame
                        $this->_autoLibObj->_sessionObj->switch_to_frame();
                        // : End
                        
                        // : Load Planningboard to rid of iframe loading on every page from here on
                        $this->_autoLibObj->_sessionObj->open($this->_maxurl . self::PB_URL);
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]");
                        });
                        // : End
                        
                        break;
                    }
                case 3:
                    {
                        // Load MAX home page
                        $session = $this->_autoLibObj->_sessionObj;
                        $this->_autoLibObj->_sessionObj->open($this->_maxurl);
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//*[text()='Sign In']");
                        });

                        // : Assert element present
                        $this->_autoLibObj->assertElementPresent("xpath", "//*[@id='identification']");
                        $this->_autoLibObj->assertElementPresent("xpath", "//*[@id='password']");
                        $this->_autoLibObj->assertElementPresent("xpath", "//*[@id='btn_Sign_In']");
                        // : End
                        
                        // Send keys to input text box
                        $e = $this->_autoLibObj->_sessionObj->element("xpath", "//*[@id='identification']")->sendKeys($_uname);
                        // Send keys to input text box
                        $e = $this->_autoLibObj->_sessionObj->element("xpath", "//*[@id='password']")->sendKeys($_pwd);
                        
                        // Click login button
                        $this->_autoLibObj->_sessionObj->element("xpath", "//*[@id='btn_Sign_In']")->click();
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//*[text()='My Tasks']");
                        });
                        
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

    /**
     * MAX_LoginLogout::maxLogout
     * Log out of MAX
     */
    public function maxLogout($_version = automationLibrary::DEFAULT_MAX_VERSION)
    {
        try {
            // : Tear Down
            $session = $this->_autoLibObj->_sessionObj;
            
            switch (intval($_version)) {
                case 2: {
                    // Click the logout link
                    $this->_autoLibObj->_sessionObj->element('xpath', "//*[contains(@href,'/logout')]")->click();
                    // Wait for page to load and for elements to be present on page
                    $e = $this->_autoLibObj->_wObj->until(function ($session)
                    {
                        return $session->element('css selector', 'input[id=identification]');
                    });
                    $this->_autoLibObj->assertElementPresent('css selector', 'input[id=identification]');       
                }
                case 3:
                default: {
                    // Click the logout link
                    $this->_autoLibObj->_sessionObj->element("xpath", "//*[@id='loggedinUser']")->click();

                    /*$e = $this->_autoLibObj->_wObj->until(function ($session)
                    {
                        //return $session->element("xpath", "//*[not(contains(@style,'display:none')) and (@id='admin-dropdown')]/li/a[@][@id=logout]");
                        return $session->element("xpath", "//a[id='logout']");
                    });*/
                    sleep(2);
                    $this->_autoLibObj->_sessionObj->element("xpath", "//*[@id='admin-dropdown']/li[5]")->click();
                    
                    // Wait for page to load and for elements to be present on page
                    
                    $e = $this->_autoLibObj->_wObj->until(function ($session)
                    {
                        return $session->element("xpath", "//*[text()='Sign In']");
                    });                  
                }
            }
        } catch (Exception $e) {
            // Store error message into static array
            $this->_errors[] = $e->getMessage();
            return FALSE;
        }
        
        return TRUE;
        // : End
    }

    /**
     * MAX_LoginLogout::maxLogout
     * Use search box in MAX V2
     */
    public function maxSearch($_tripNumber)
    {
        try {
            $session = $this->_autoLibObj->_sessionObj;
            
            $this->_autoLibObj->assertElementPresent('css selector', 'input.inputtext');
            $this->_autoLibObj->assertElementPresent('css selector', 'div.search-button-image');
            $_autoLibObj->_sessionObj->element('css selector', 'input.inputtext')->sendKeys("tripNumber:$_tripNumber");
            $_autoLibObj->_sessionObj->element('css selector', 'div.search-button-image')->click();
            
            $this->_tmp = $_tripNumber;
            
            // Wait for page to load and for elements to be present on page
            $e = $this->_autoLibObj->_wObj->until(function ($session)
            {
                return $session->element('xpath', '//a[text()="' . self::$_tmp . '"]');
            });
        } catch (Exception $e) {
            // Store error message into static array
            $this->_errors[] = $e->getMessage();
            return FALSE;
        }
        
        return TRUE;
        // : End
    }
    // : End - Public Functions
    
    // : Private Functions
    // : End - Private Functions
}

