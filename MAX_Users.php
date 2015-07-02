<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes

// : End

/**
 * MAX_Users.php
 *
 * @package MAX_Users
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
class maxUsers
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
    protected $_tmp;

    protected $_maxurl;

    protected $_autoLibObj;

    protected $_errors = array();
    // : End
    
    // : Magic Functions
    /**
     * MAX_Users::__construct
     * Class constructor
     */
    public function __construct(&$_autoObj, $_maxurl, $_first_name, $_last_name, $_email, $_job_title, $_company, $_groups, $_address = null)
    {
        if (is_object($_autoObj) && is_string($_maxurl)) {
            // Save referenced automation library object containing session and phpunit objects
            $this->_autoLibObj = $_autoObj;
            $this->_maxurl = $_maxurl;
        }
    }

    /**
     * MAX_Users::__destruct
     * Class destructor
     */
    public function __destruct()
    {
        unset($this);
    }
    
    // : End - Magic Functions
    
    // : Public Functions
    /**
     * MAX_Users::getLastError
     * Return last encountered error
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
     * MAX_Users::maxCreateNewUser
     * Follows the process involved on MAX to create a new user
     */
    public function maxCreateNewUser($_version = automationLibrary::DEFAULT_MAX_VERSION)
    {
        try {
            switch ($_version) {
                case 3:
                {   
                    $e = $this->_autoLibObj->_wObj->until(function ($session)
                    {
                        return $session->element("xpath", "//*[text()='My Tasks']");
                    });
                    
                    $this->_autoLibObj->assertElementPresent("xpath", "//li[@class='dropdown ng-scope']/a[@id='Admin']");
                    $this->_autoLibObj->_sessionObj->element("xpath", "//li[@class='dropdown ng-scope']/a[@id='Admin']")->click();
                    
                    $e = $this->_autoLibObj->_wObj->until(function ($session)
                    {
                        return $session->element("xpath", "//li[@class='dropdown ng-scope open' and @ng-repeat='item1 in menuItems']/a[@id='Admin']");
                    });
                    
                    $this->_autoLibObj->assertElementPresent("xpath", "//a[@id='Users' and text()='Users']");
                    $this->_autoLibObj->_sessionObj->element("xpath", "//a[@id='Users' and text()='Users']")->click();
                    
                    $e = $this->_autoLibObj->_wObj->until(function ($session)
                    {
                        return $session->element("xpath", "//h1[contains(text(),'Users')]");
                    });
                    
                    $this->_autoLibObj->assertElementPresent("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]]");
                    $this->_autoLibObj->_sessionObj->element("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]]")->click();
                    
                    $e = $this->_autoLibObj->_wObj->until(function ($session)
                    {
                        return $session->element("xpath", "//h1[contains(text(),'Invite New User')]");
                    });
                    
                    // : Input Fields
                    $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='text' and @name='email' and @id='email' and @ng-controller='TextBoxCtrl']");
                    $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='text' and @name='invitationMessage' and @id='invitationMessage' and @ng-controller='TextBoxCtrl']");
                    
                    $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='text' and @name='email' and @id='email' and @ng-controller='TextBoxCtrl']")->sendKeys('dummyuser002@max.co.za');
                    $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='text' and @name='invitationMessage' and @id='invitationMessage' and @ng-controller='TextBoxCtrl']")->sendKeys('This user was created by Barloworld Transport PHP Automation Library');
                    // : End
                    
                    // : Radio Buttons - Groups
                    $this->_autoLibObj->assertElementPresent("xpath", "//div[@class='radiobutton ng-scope']/label[@for='groups' and contains(text(),'BU - Dedicated')]");
                    
                    $this->_autoLibObj->_sessionObj->element("xpath", "//div[@class='radiobutton ng-scope']/label[@for='groups' and contains(text(),'BU - Dedicated')]")->click();
                    // : End
                    
                    $this->_autoLibObj->assertElementPresent("xpath", "//button[@class='btn ng-scope ng-binding btn-success' and @id='btn_Continue' and @type='submit']");

                    $this->_autoLibObj->_sessionObj->element("xpath", "//button[@class='btn ng-scope ng-binding btn-success' and @id='btn_Continue' and @type='submit']")->click();
                    
                    $e = $this->_autoLibObj->_wObj->until(function ($session)
                    {
                        return $session->element("xpath", "//div[@class='alert' and @id='result_msg_flash']/strong[contains(text(),'The new user will be notified via e-mail to login.')]");
                    });
                    
                    break;
                }
                case 2:
                default: {
                    $this->_errors[] = "User administration is exclusively available to MAX V3 only.";
                    return FALSE;
                    break;
                }
            }
            
        } catch (Exception $e) {
            // Store error message into static array
            $this->_errors[] = $e->getMessage();
            return FALSE;
        }
        /* If code reaches this point then there was no errors in running the code, therefore return TRUE
         * NOTE: This will be changed to check if the new user has been created then pass on the basis of no thrown exceptions and that the new user exists
         * If the user exists and no errors are encountered then that would be a real pass condition and then TRUE should be returned
        */
        return TRUE;
        // : End
    }
    // : End - Public Functions
    
    // : Private Functions
    // : End - Private Functions
}

