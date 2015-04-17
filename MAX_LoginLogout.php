<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes

//require_once 'automationLibrary.php';

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
    public static $_tmp;
    public static $_errors = array();
    // : End
    
    /**
     * MAX_LoginLogout::maxLogin
     * Log into MAX
     */
    public static function maxLogin(&$_session, &$w, &$_phpunit_fw_obj, $_uname, $_pwd, $_welcome, $_maxurl)
    {
        try {
            // : Log into MAX
            // Load MAX home page
            $session = $_session;
            $_session->open($_maxurl);
            
            // : Wait for page to load and for elements to be present on page
            $e = $w->until(function ($session)
            {
                return $session->element('css selector', "#contentFrame");
            });
            
            $iframe = $_session->element('css selector', '#contentFrame');
            $_session->switch_to_frame($iframe);
            
            $e = $w->until(function ($session)
            {
                return $session->element('css selector', 'input[id=identification]');
            });
            // : End
            
            // : Assert element present
            automationLibrary::assertElementPresent('css selector', 'input[id=identification]', $_session, $_phpunit_fw_obj);
            automationLibrary::assertElementPresent('css selector', 'input[id=password]', $_session, $_phpunit_fw_obj);
            automationLibrary::assertElementPresent('css selector', 'input[name=submit][type=submit]', $_session, $_phpunit_fw_obj);
            // : End
            
            // Send keys to input text box
            $e = $_session->element('css selector', 'input[id=identification]')->sendKeys($_uname);
            // Send keys to input text box
            $e = $_session->element('css selector', 'input[id=password]')->sendKeys($_pwd);
            
            // Click login button
            $_session->element('css selector', 'input[name=submit][type=submit]')->click();
            // Switch out of frame
            $_session->switch_to_frame();
            
            // : Wait for page to load and for elements to be present on page
            $e = $w->until(function ($session)
            {
                return $session->element('css selector', "#contentFrame");
            });
            $iframe = $_session->element('css selector', '#contentFrame');
            $_session->switch_to_frame($iframe);
            
            self::$_tmp = $_welcome;
            $e = $w->until(function ($session)
            {
                return $session->element("xpath", "//*[text()='" . self::$_tmp . "']");
            });
            automationLibrary::assertElementPresent("xpath", "//*[text()='" . $_welcome . "']", $_session, $_phpunit_fw_obj);
            // Switch out of frame
            $_session->switch_to_frame();
            // : End
            
            // : Load Planningboard to rid of iframe loading on every page from here on
            $_session->open($_maxurl . self::PB_URL);
            $e = $w->until(function ($session)
            {
                return $session->element("xpath", "//*[contains(text(),'You Are Here') and contains(text(), 'Planningboard')]");
            });
            // : End
        } catch (Exception $e) {
            // Store error message into static array
            self::$_errors[] = $e->getMessage();
            return FALSE;
        }
        return TRUE;
    }

    /**
     * MAX_LoginLogout::maxLogout
     * Log out of MAX
     */
    public static function maxLogout(&$_session, &$w, &$_phpunit_fw_obj)
    {
        try {
        // : Tear Down
        // Click the logout link
        $_session->element('xpath', "//*[contains(@href,'/logout')]")->click();
        // Wait for page to load and for elements to be present on page
        $e = $w->until(function ($session)
        {
            return $session->element('css selector', 'input[id=identification]');
        });
        automationLibrary::assertElementPresent('css selector', 'input[id=identification]', $_session, $_phpunit_fw_obj);
        // Terminate session
        $_session->close();
        } catch (Exception $e) {
            // Store error message into static array
            self::$_errors[] = $e->getMessage();
            return FALSE;    
        }
        return TRUE;
        // : End
    }
    // : End - Public Functions
    
    // : Private Functions
    // : End - Private Functions
}

