<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes

// : End

/**
 * MAX_CreateNewUser.php
 *
 * @package MAX_CreateNewUser
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
class MAX_CreateNewUser
{
    
    // : Constants
    const DS = DIRECTORY_SEPARATOR;
    // : End - Constants
    
    // : Variables
    public $_sessionObj;
    public $_phpunitObj;
    public $_waitObj; 
    protected $_tmp;
    // : End
    
    /**
     * MAX_CreateNewUser::maxCreateNewUser
     * Follows the process involved on MAX to create a new user
     */
    public static function maxCreateNewUser()
    {
        try {
            // Wait for page to load and for elements to be present on page
            $e = $w->until(function ($session)
            {
                return $session->element('xpath', '//a[text()="' . self::$_tmp . '"]');
            });

        } catch (Exception $e) {
            // Store error message into static array
            self::$_errors[] = $e->getMessage();
            return FALSE;
        }
    
        return TRUE;
        // : End
    }
    // : End - Public Functions
    
    // : Magic
    
    /**
     * MAX_CreateNewUser::__construct()
     * Class constructor used to verify data given for the MAX create user process is correct and complete before running the process
     * @param object: $_session
     * @param object: $_w
     * @param object: $_phpunit_fw_obj
     */
    public function __construct(&$_session, &$_w, &$_phpunit_fw_obj, $_first_name, $_last_name, $_email, $_job_title, $_company, $_groups) {
        try {
        // : Save referenced objects into object
        if ($_session && $_w && $_phpunit_fw_obj) {
            var_dump($_session);
            
            $this->_sessionObj = $_session;
            $this->_waitObj = $_w;
            $this->_phpunitObj = $_phpunit_fw_obj;
        
        }
        // : End
        } catch (Exception $e) {
            return FALSE;
        }
        // If reaches here then code passed
        return TRUE;
    }
    
    // : End
    
    // : Private Functions
    // : End - Private Functions
}

