<?php
// Set error reporting level for this script
error_reporting(E_ALL);

require_once 'MAX_API_Get.php';

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

    const DEFAULT_MODE = "test";
    
    const ERR_RECORD_NOT_FOUND = "Record not found using method: %s and value: %d";
    // : End - Constants
    
    // : Variables
    protected $_tmp;

    protected $_maxurl;

    protected $_autoLibObj;

    protected $_errors = array();

    protected $_first_name;

    protected $_last_name;

    protected $_email;

    protected $_job_title;

    protected $_company;

    protected $_groups;

    protected $_address;

    protected $_passwd;

    protected $_maxapiget;
    // : End
    
    // : Magic Functions
    /**
     * MAX_Users::__construct
     * Class constructor
     */
    public function __construct(&$_autoObj, $_maxurl, $_first_name, $_last_name, $_email, $_job_title, $_company, $_groups, $_address = null, $_passwd = null)
    {
        if (is_object($_autoObj) && is_string($_maxurl) && is_string($_first_name) && is_string($_last_name) && is_string($_email) && is_string($_job_title) && is_string($_company) && is_array($_groups)) {
            $_mode = self::DEFAULT_MODE;
            
            // Save referenced automation library object containing session and phpunit objects
            $this->_autoLibObj = $_autoObj;
            $this->_maxurl = $_maxurl;
            
            // : Save variables needed to create the new user
            $this->_first_name = $_first_name;
            $this->_last_name = $_last_name;
            $this->_email = $_email;
            $this->_job_title = $_job_title;
            $this->_company = $_company;
            $this->_groups = $_groups;
            // : End
            
            // : Save optional variables to be used to create new user if they exist
            if ($this->_address) {
                $this->_address = $_address;
            } else {
                $this->_address = null;
            }
            
            if ($this->_passwd) {
                $this->_passwd = $_passwd;
            } else {
                $this->_passwd = null;
            }
            
            if ($this->_autoLibObj->getMode()) {
                $_mode = $this->_autoLibObj->getMode();
            }
            
            // Create instance of MAX_API_Get Class
            $this->_maxapiget = new MAX_API_Get($_mode);
            
            // : End
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
    
    // : Getters
    
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
    
    // : End
    
    // : Setters
    
    /**
     * MAX_Users::setUserEmail
     * Set user email address
     */
    public function setUserEmail($_email)
    {
        if ($_email && is_string($_email)) {
            $this->_email = $_email;
        }
    }

    /**
     * MAX_Users::setUserNames
     * Set user first and last names
     */
    public function setUserNames($_first_name, $_last_name)
    {
        if ($_first_name && $_last_name && is_string($_first_name) && is_string($_last_name)) {
            $this->_first_name = $_first_name;
            $this->_last_name = $_last_name;
        }
    }

    /**
     * MAX_Users::setUserJobTitle
     * Set user job title
     */
    public function setUserJobTitle($_job_title)
    {
        if ($_job_title && is_string($_job_title)) {
            $this->_job_title = $_job_title;
        }
    }

    /**
     * MAX_Users::setUserCompany
     * Set user company name
     */
    public function setUserCompany($_company)
    {
        if ($_company && is_string($_company)) {
            $this->_company = $_company;
        }
    }

    /**
     * MAX_Users::setUserGroups
     * Set user group IDs
     */
    public function setUserGroups($_groups)
    {
        if ($_groups && is_array($_groups)) {
            $this->_groups = $_groups;
        }
    }

    /**
     * MAX_Users::setUserAddress
     * Set user password
     */
    public function setUserAddress($_address)
    {
        if ($_address && is_string($_address)) {
            $this->_address = $_address;
        }
    }

    /**
     * MAX_Users::setUserPwd
     * Set user group IDs
     */
    public function setUserPasswd($_address)
    {
        if ($_passwd && is_string($_passwd)) {
            $this->_passwd = $_passwd;
        }
    }
    
    // : End
    
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
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//li[@class='dropdown ng-scope']/a[@id='Admin']");
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
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]");
                        });
                        
                        $this->_autoLibObj->assertElementPresent("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]");
                        $this->_autoLibObj->_sessionObj->element("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]")->click();
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//h1[contains(text(),'Invite New User')]");
                        });
                        
                        // : Input Fields
                        $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='text' and @name='email' and @id='email' and @ng-controller='TextBoxCtrl']");
                        $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='text' and @name='invitationMessage' and @id='invitationMessage' and @ng-controller='TextBoxCtrl']");
                        
                        $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='text' and @name='email' and @id='email' and @ng-controller='TextBoxCtrl']")->sendKeys($this->_email);
                        $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='text' and @name='invitationMessage' and @id='invitationMessage' and @ng-controller='TextBoxCtrl']")->sendKeys('This user was created by Barloworld Transport PHP Automation Library');
                        // : End
                        
                        // : Radio Buttons - Groups
                        foreach ($this->_groups as $_group_value) {
                            
                            $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='checkbox' and @id='groups' and @name='groups' and @value='$_group_value']");
                            $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='checkbox' and @id='groups' and @name='groups' and @value='$_group_value']")->click();
                        }
                        // : End
                        
                        // : Click Continue button to save the user
                        $this->_autoLibObj->assertElementPresent("xpath", "//button[@class='btn ng-scope ng-binding btn-success' and @id='btn_Continue' and @type='submit']");
                        $this->_autoLibObj->_sessionObj->element("xpath", "//button[@class='btn ng-scope ng-binding btn-success' and @id='btn_Continue' and @type='submit']")->click();
                        // : End
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//div[@class='alert' and @id='result_msg_flash']/strong[contains(text(),'The new user will be notified via e-mail to login.')]");
                        });
                        // : End
                        
                        break;
                    }
                case 2:
                default:
                    {
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
        /*
         * If code reaches this point then there was no errors in running the code, therefore return TRUE
         * NOTE: This will be changed to check if the new user has been created then pass on the basis of no thrown exceptions and that the new user exists
         * If the user exists and no errors are encountered then that would be a real pass condition and then TRUE should be returned
         */
        return TRUE;
        // : End
    }

    /**
     * MAX_Users::maxCheckUserExists
     * Run API to check if a user exists
     */
    public function maxCheckUserExists($_email)
    {
        try {
            
            $this->_maxapiget->setObject('person');
            $this->_maxapiget->setFilter("email like '$_email'");
            $this->_maxapiget->runApiQuery();
            $_result = $this->_maxapiget->getData();
            
            if ($_result && array_key_exists("ID")) {
                $_person_id = $_result["ID"];
            }
            
            if (isset($_person_id)) {
                $this->_maxapiget->setObject('PermissionUser');
                $this->_maxapiget->setFilter("person_id=$_person_id");
                $this->_maxapiget->runApiQuery();
                $_result = $this->_maxapiget->getData();
                
                if ($_result && array_key_exists("ID")) {
                    $_result_id = $_result["ID"];
                }
            }
            
            //  Returns the PermissionUser ID that can be used to load the user's profile
            if (isset($_result_id)) {
                return $_result_id;
            } else {
                $_errmsg = preg_replace("/%s/", __FUNCTION__, self::ERR_RECORD_NOT_FOUND);
                $_errmsg = preg_replace("/%s/", $_email, $_errmsg);
                $this->_errors[] = $_errmsg;
                return FALSE;
            }
            
        } catch (Exception $e) {
            // Store error message into static array
            $this->_errors[] = $e->getMessage();
            return FALSE;
        }
    }

    /**
     * MAX_Users::maxUpdateContactDetails
     * Follows the process involved on MAX to update a users contact details
     */
    public function maxUpdateContactDetails($_version = automationLibrary::DEFAULT_MAX_VERSION)
    {
        try {
            switch ($_version) {
                case 3:
                    {
                        // Set session local variable
                        $session = $this->_autoLibObj->_sessionObj;
                        
                        $_user_id = $this->maxCheckUserExists($this->_email);
                        
                        if ($_user_id) {
                            $this->_maxapiget->setObject('PermissionUser');
                            $_url = $this->_maxapiget->getApiDetailURLString($_user_id);
                        }
                        
                        if (isset($_url)) {
                            
                            if ($_url && is_string($_url)) {
                                
                                $this->_autoLibObj->_sessionObj->open($this->_maxurl . $_url);
                                
                                $e = $this->_autoLibObj->_wObj->until(function ($session)
                                {
                                    return $session->element("xpath", "//h1[contains(text(),'" . $this->_email . "')]");
                                });                              

                                $e = $this->_autoLibObj->_wObj->until(function ($session)
                                {
                                    return $session->element("xpath", "//button[@id='btn_Update_User' and @type='submit']");
                                });         

                                $this->_autoLibObj->assertElementPresent("xpath", "//button[@id='btn_Update_Contact Details' and @name='btn_Update_Contact Details' and @type='submit' and @class='btn ng-scope ng-binding btn-warning']");
                                $this->_autoLibObj->_sessionObj->element("xpath", "//button[@id='btn_Update_Contact Details' and @name='btn_Update_Contact Details' and @type='submit' and @class='btn ng-scope ng-binding btn-warning']")->click();
                                
                                $e = $this->_autoLibObj->_wObj->until(function ($session)
                                {
                                    return $session->element("xpath", "//label[@id='lbl_first_name' and @for='first_name' and @class='ng-binding' and contains(text(),'First Name')]");
                                });
                                
                                $this->_autoLibObj->assertElementPresent("xpath", "//label[@id='lbl_first_name' and @for='first_name' and @class='ng-binding' and contains(text(),'First Name')]");
                                $this->_autoLibObj->assertElementPresent("xpath", "//input[@id='first_name' and @name='first_name' and @type='text' and @ng-controller='TextBoxCtrl']");
                                $this->_autoLibObj->assertElementPresent("xpath", "//input[@id='last_name' and @name='last_name' and @type='text' and @ng-controller='TextBoxCtrl']");
                                $this->_autoLibObj->assertElementPresent("xpath", "//input[@id='email' and @name='email' and @type='text' and @ng-controller='TextBoxCtrl']");
                                $this->_autoLibObj->assertElementPresent("xpath", "//input[@id='jobTitle' and @name='jobTitle' and @type='text' and @ng-controller='TextBoxCtrl']");
                                $this->_autoLibObj->assertElementPresent("xpath", "//input[@id='company' and @name='company' and @type='text' and @ng-controller='TextBoxCtrl']");
                                $this->_autoLibObj->assertElementPresent("xpath", "//input[@id='address_line_1' and @name='address_line_1' and @type='text' and @ng-controller='TextBoxCtrl']");
                                $this->_autoLibObj->assertElementPresent("xpath", "//button[@id='btn_Save_and Complete' and @type='submit' and @class='btn ng-scope ng-binding btn-success']");
                                
                                $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='first_name' and @name='first_name' and @type='text' and @ng-controller='TextBoxCtrl']")->clear();
                                $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='first_name' and @name='first_name' and @type='text' and @ng-controller='TextBoxCtrl']")->sendKeys($this->_first_name);
                                
                                $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='last_name' and @name='last_name' and @type='text' and @ng-controller='TextBoxCtrl']")->clear();
                                $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='last_name' and @name='last_name' and @type='text' and @ng-controller='TextBoxCtrl']")->sendKeys($this->_last_name);;
                                
                                $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='email' and @name='email' and @type='text' and @ng-controller='TextBoxCtrl']")->clear();
                                $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='email' and @name='email' and @type='text' and @ng-controller='TextBoxCtrl']")->sendKeys($this->_email);
                                
                                $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='jobTitle' and @name='jobTitle' and @type='text' and @ng-controller='TextBoxCtrl']")->clear();
                                $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='jobTitle' and @name='jobTitle' and @type='text' and @ng-controller='TextBoxCtrl']")->sendKeys($this->_job_title);
                                
                                $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='company' and @name='company' and @type='text' and @ng-controller='TextBoxCtrl']")->clear();
                                $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='company' and @name='company' and @type='text' and @ng-controller='TextBoxCtrl']")->sendKeys($this->_company);
                                
                                if ($this->_address) {
                                    $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='address_line_1' and @name='address_line_1' and @type='text' and @ng-controller='TextBoxCtrl']")->clear();
                                    $this->_autoLibObj->_sessionObj->element("xpath", "//input[@id='address_line_1' and @name='address_line_1' and @type='text' and @ng-controller='TextBoxCtrl']")->sendKeys($this->_address);
                                }
                                
                                $this->_autoLibObj->_sessionObj->element("xpath", "//button[@id='btn_Save_and Complete' and @type='submit' and @class='btn ng-scope ng-binding btn-success']")->click();
                                
                                $e = $this->_autoLibObj->_wObj->until(function ($session)
                                {
                                    return $session->element("xpath", "//div[@class='alert' and @id='result_msg_flash']/strong[contains(text(),'Update Contact Details')]");
                                });
                                
                                $e = $this->_autoLibObj->_wObj->until(function ($session)
                                {
                                    return $session->element("xpath", "//button[@id='btn_Update_User' and @type='submit']");
                                });

                            }
                        }
                        
                        break;
                    }
                case 2:
                default:
                    {
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
        
        return TRUE;
        // : End
    }

    /**
     * MAX_Users::maxSetUserPassword
     * Follows the process involved on MAX to set a users password
     */
    public function maxSetUserPassword($_version = automationLibrary::DEFAULT_MAX_VERSION)
    {
        try {
            switch ($_version) {
                case 3:
                    
                    {
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//*[text()='My Tasks']");
                        });
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//li[@class='dropdown ng-scope']/a[@id='Admin']");
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
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]");
                        });
                        
                        $this->_autoLibObj->assertElementPresent("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]");
                        $this->_autoLibObj->_sessionObj->element("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]")->click();
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//h1[contains(text(),'Invite New User')]");
                        });
                        
                        // : Input Fields
                        $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='text' and @name='email' and @id='email' and @ng-controller='TextBoxCtrl']");
                        $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='text' and @name='invitationMessage' and @id='invitationMessage' and @ng-controller='TextBoxCtrl']");
                        
                        $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='text' and @name='email' and @id='email' and @ng-controller='TextBoxCtrl']")->sendKeys($this->_email);
                        $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='text' and @name='invitationMessage' and @id='invitationMessage' and @ng-controller='TextBoxCtrl']")->sendKeys('This user was created by Barloworld Transport PHP Automation Library');
                        // : End
                        
                        // : Radio Buttons - Groups
                        foreach ($this->_groups as $_group_value) {
                            
                            $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='checkbox' and @id='groups' and @name='groups' and @value='$_group_value']");
                            $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='checkbox' and @id='groups' and @name='groups' and @value='$_group_value']")->click();
                        }
                        // : End
                        
                        // : Click Continue button to save the user
                        $this->_autoLibObj->assertElementPresent("xpath", "//button[@class='btn ng-scope ng-binding btn-success' and @id='btn_Continue' and @type='submit']");
                        $this->_autoLibObj->_sessionObj->element("xpath", "//button[@class='btn ng-scope ng-binding btn-success' and @id='btn_Continue' and @type='submit']")->click();
                        // : End
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//div[@class='alert' and @id='result_msg_flash']/strong[contains(text(),'The new user will be notified via e-mail to login.')]");
                        });
                        // : End
                        
                        break;
                    }
                case 2:
                default:
                    {
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
        
        return TRUE;
        // : End
    }

    /**
     * MAX_Users::maxUpdatePersonalGroup
     * Follows the process involved on MAX to update the users personal group ownership and permission
     */
    public function maxUpdatePersonalGroup($_version = automationLibrary::DEFAULT_MAX_VERSION)
    {
        try {
            switch ($_version) {
                case 3:
                    {
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//*[text()='My Tasks']");
                        });
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//li[@class='dropdown ng-scope']/a[@id='Admin']");
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
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]");
                        });
                        
                        $this->_autoLibObj->assertElementPresent("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]");
                        $this->_autoLibObj->_sessionObj->element("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]")->click();
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//h1[contains(text(),'Invite New User')]");
                        });
                        
                        // : Input Fields
                        $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='text' and @name='email' and @id='email' and @ng-controller='TextBoxCtrl']");
                        $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='text' and @name='invitationMessage' and @id='invitationMessage' and @ng-controller='TextBoxCtrl']");
                        
                        $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='text' and @name='email' and @id='email' and @ng-controller='TextBoxCtrl']")->sendKeys($this->_email);
                        $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='text' and @name='invitationMessage' and @id='invitationMessage' and @ng-controller='TextBoxCtrl']")->sendKeys('This user was created by Barloworld Transport PHP Automation Library');
                        // : End
                        
                        // : Radio Buttons - Groups
                        foreach ($this->_groups as $_group_value) {
                            
                            $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='checkbox' and @id='groups' and @name='groups' and @value='$_group_value']");
                            $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='checkbox' and @id='groups' and @name='groups' and @value='$_group_value']")->click();
                        }
                        // : End
                        
                        // : Click Continue button to save the user
                        $this->_autoLibObj->assertElementPresent("xpath", "//button[@class='btn ng-scope ng-binding btn-success' and @id='btn_Continue' and @type='submit']");
                        $this->_autoLibObj->_sessionObj->element("xpath", "//button[@class='btn ng-scope ng-binding btn-success' and @id='btn_Continue' and @type='submit']")->click();
                        // : End
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//div[@class='alert' and @id='result_msg_flash']/strong[contains(text(),'The new user will be notified via e-mail to login.')]");
                        });
                        // : End
                        
                        break;
                    }
                case 2:
                default:
                    {
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
        
        return TRUE;
        // : End
    }

    /**
     * MAX_Users::maxCreatePreference
     * Follows the process involved on MAX to create the user preferences
     */
    public function maxCreatePreference($_version = automationLibrary::DEFAULT_MAX_VERSION)
    {
        try {
            switch ($_version) {
                case 3:
                    {
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//*[text()='My Tasks']");
                        });
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//li[@class='dropdown ng-scope']/a[@id='Admin']");
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
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]");
                        });
                        
                        $this->_autoLibObj->assertElementPresent("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]");
                        $this->_autoLibObj->_sessionObj->element("xpath", "//button[@class='btn btn-inverse' and contains(text(),'Create')]")->click();
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//h1[contains(text(),'Invite New User')]");
                        });
                        
                        // : Input Fields
                        $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='text' and @name='email' and @id='email' and @ng-controller='TextBoxCtrl']");
                        $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='text' and @name='invitationMessage' and @id='invitationMessage' and @ng-controller='TextBoxCtrl']");
                        
                        $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='text' and @name='email' and @id='email' and @ng-controller='TextBoxCtrl']")->sendKeys($this->_email);
                        $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='text' and @name='invitationMessage' and @id='invitationMessage' and @ng-controller='TextBoxCtrl']")->sendKeys('This user was created by Barloworld Transport PHP Automation Library');
                        // : End
                        
                        // : Radio Buttons - Groups
                        foreach ($this->_groups as $_group_value) {
                            
                            $this->_autoLibObj->assertElementPresent("xpath", "//input[@type='checkbox' and @id='groups' and @name='groups' and @value='$_group_value']");
                            $this->_autoLibObj->_sessionObj->element("xpath", "//input[@type='checkbox' and @id='groups' and @name='groups' and @value='$_group_value']")->click();
                        }
                        // : End
                        
                        // : Click Continue button to save the user
                        $this->_autoLibObj->assertElementPresent("xpath", "//button[@class='btn ng-scope ng-binding btn-success' and @id='btn_Continue' and @type='submit']");
                        $this->_autoLibObj->_sessionObj->element("xpath", "//button[@class='btn ng-scope ng-binding btn-success' and @id='btn_Continue' and @type='submit']")->click();
                        // : End
                        
                        $e = $this->_autoLibObj->_wObj->until(function ($session)
                        {
                            return $session->element("xpath", "//div[@class='alert' and @id='result_msg_flash']/strong[contains(text(),'The new user will be notified via e-mail to login.')]");
                        });
                        // : End
                        
                        break;
                    }
                case 2:
                default:
                    {
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
        
        return TRUE;
        // : End
    }
    
    // : End - Public Functions
    
    // : Private Functions
    // : End - Private Functions
}

