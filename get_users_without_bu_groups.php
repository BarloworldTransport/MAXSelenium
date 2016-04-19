<?php
// Error reporting
error_reporting ( E_ALL );

// : Includes
// MySQL query pull and return data class
require dirname ( __FILE__ ) . '/PullDataFromMySQLQuery.php';
// : End

/**
 * get_users_without_bu_groups.php
 *
 * @package get_users_without_bu_groups
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
class get_users_without_bu_groups {
	// : Constants
	const MAXDB = "max2";
	const T24DB = "application_3";
	const DS = DIRECTORY_SEPARATOR;
	const DELIMITER = ',';
	const ENCLOSURE = '"';
	const CSV_LIMIT = 0;
	const FILE_NOT_FOUND = "The following path and filename could not be found: %s";
	const COULD_NOT_OPEN_FILE = "Could not open the specfied file %s";
	const FILE_EMPTY = "The following file is empty: %s";
	const COLUMN_VALIDATION_FAIL = "Not all columns are present in the following file %s";
	const DIR_NOT_FOUND = "The directory path was not found: %s.";
	const DB_HOST = "192.168.1.19";
	
	// : Variables
	protected $_fileName;
	protected $_errors;
	protected $_data;
	protected $_bugroups;
	protected $_bu;
	
	// : Public functions
	
	/**
	 * get_users_without_bu_groups::exportData()
	 *
	 * @param string: $this->_errors;
	 */
	public function exportData($_file) {
		if (! empty ( $this->_errors )) {
			return $this->_errors;
		} else {
			return FALSE;
		}
	}
	
	// : Accessors
	
	/**
	 * get_users_without_bu_groups::getError()
	 *
	 * @param string: $this->_errors;        	
	 */
	public function getError() {
		if (! empty ( $this->_errors )) {
			return $this->_errors;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * get_users_without_bu_groups::getBU()
	 *
	 * @param string: $this->_errors;
	 */
	public function getBU() {
		if (! empty ( $this->_bu )) {
			return $this->_bu;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * get_users_without_bu_groups::getBUGroups()
	 *
	 * @param string: $this->_bugroups;
	 */
	public function getBUGroups() {
		if (! empty ( $this->_bugroups )) {
			return $this->_bugroups;
		} else {
			return FALSE;
		}
	}
	
	// : End
	// : Setters
	
	/**
	 * get_users_without_bu_groups::setFileName($_setFile)
	 *
	 * @param string: $_setFile        	
	 */
	public function setFileName($_setFile) {
		$this->_fileName = $_setFile;
	}
	
	// : End
	
	// : Magic
	/**
	 * get_users_without_bu_groups::__construct()
	 * Class constructor
	 */
	public function __construct($_file) {
		try {
			// Store sql queries
			$_queries = array (
					"select pu.id, p.first_name, p.last_name, p.email, pu.personal_group_id, pu.status from person as p left join permissionuser as pu on (pu.person_id=p.id) where pu.status = 1 order by pu.id asc;",
					"select grl.id, gr.name from group_role_link as grl left join `group` as gr on (gr.id=grl.group_id) where grl.played_by_group_id = %s and grl.group_id IN (%g) order by grl.id asc;",
					"select id, name from `group` where name like 'bu%-%' order by id asc;",
					"select id, name from udo_businessunit;" 
			);
			
			// Create new SQL Query class object
			$_mysqlQueryMAX = new PullDataFromMySQLQuery ( self::MAXDB, self::DB_HOST );
			$this->_data = ( array ) array ();
			$_buGroups = ( array ) array ();
			$_grpList = ( string ) "";
			// : Build List of Business Units by pulling them from MAX
			$_result = $_mysqlQueryMAX->getDataFromQuery ( $_queries [3] );
			if ($_result) {
				foreach ( $_result as $key => $value ) {
					if (array_key_exists ( "name", $value )) {
						$this->_bu [] = $value ["name"];
					}
				}
			}
			// : End
			
			// : Build string for regexp that will be used to find BU groups on MAX
			$_group_regexp = ( string ) "";
			foreach ( $this->_bu as $key => $value ) {
				
				$_1 = substr ( $value, 0, 1 );
				$_part = substr ( $value, - (count ( $value ) - 2) );
				$_detect2Names = preg_split ( "/\s/", $_part );
				if ($_detect2Names) {
					if (count ( $_detect2Names ) > 1) {
						foreach ( $_detect2Names as $_key => $_value ) {
							if ($_value) {
								if (! $_key) {
									$_part = $_value;
								} else {
									$_part .= "\s" . ucfirst ( strtolower ( $_value ) );
								}
							}
						}
					}
				}
				
				$_buname = "[" . strtoupper ( $_1 ) . strtolower ( $_1 ) . $_part . "]";
				
				if (! $_group_regexp) {
					$_group_regexp = $_buname;
				} else {
					$_group_regexp .= "|$_buname";
				}
			}
			// : End
			
			$_buGroups = $_mysqlQueryMAX->getDataFromQuery ( $_queries [2] );
			if (! $_buGroups) {
				throw new Exception ( "No business unit groups found in groups table on MAX using the following query:\n{$_queries[2]}" );
			} else {
				foreach ( $_buGroups as $key => $value ) {
					preg_match ("/^BU.+-.+$_group_regexp$/", $value ["name"], $_pregResults);
					if (! empty ( $_pregResults )) {
						
						// Save BU Group to class property
						$this->_bugroups[] = $value;
						
						if (empty ( $_grpList )) {
							$_grpList = $value ["id"];
						} else {
							$_grpList .= ",{$value["id"]}";	
						}
					}
				}
			}
			$_totalUsers = ( integer ) 0;
			$_maxUsers = ( array ) array ();
			$_maxUsers = $_mysqlQueryMAX->getDataFromQuery ( $_queries [0] );
			// Add headers to the array
			$this->_data [] = array (
					"ID",
					"Firstnames",
					"Surname",
					"Email",
					"PersonalGroupID",
					"Status" 
			);
			if ($_maxUsers) {
				foreach ( $_maxUsers as $key => $value ) {
					$_aQuery = preg_replace ( "/%s/", $value ["personal_group_id"], $_queries [1] );
					$_aQuery = preg_replace ( "/%g/", $_grpList, $_aQuery );
					$_result = $_mysqlQueryMAX->getDataFromQuery ( $_aQuery );
					if (empty ( $_result )) {
						if (! array_key_exists ( $value ["first_name"] . " " . $value ["last_name"], $this->_data )) {
							foreach ( $value as $aKey => $aValue ) {
								$this->_data [$value ["first_name"] . " " . $value ["last_name"]] [$aKey] = $aValue;
							}
						}
					}
				}
			} else {
				throw new Exception ( "No users found in the persons table MAX database using the following query:\n{$_queries[0]}" );
			}
			// Close database connection
			unset ( $_mysqlQueryMAX );
			
			$_csvfile = dirname ( __FILE__ ) . self::DS . "Data" . self::DS . $_file;
			
			// Return result
			if (empty ( $this->_data )) {
				return FALSE;
			} else {
				return TRUE;
			}
		} catch ( Exception $e ) {
			$this->_errors [] = $e->getMessage ();
			unset ( $_mysqlQueryMAX );
			return FALSE;
		}
	}
	
	/**
	 * get_users_without_bu_groups::__destruct()
	 * Class destructor
	 * Allow for garbage collection
	 */
	public function __destruct() {
		unset ( $this );
	}
	// : End
	
	// : Private Functions
	/**
	 * getusersBUFromList::exportToCSV($csvFile, $arr)
	 * From supplied csv file save data into multidimensional array
	 *
	 * @param string: $csvFile        	
	 * @param array: $_arr        	
	 */
	private function exportToCSV($csvFile, $_arr) {
		try {
			$_data = ( array ) array ();
			if (file_exists ( dirname ( $csvFile ) )) {
				$_handle = fopen ( $csvFile, 'w' );
				foreach ( $_arr as $key => $value ) {
					fputcsv ( $_handle, $value );
				}
				fclose ( $_handle );
			} else {
				$_msg = preg_replace ( "@%s@", $csvFile, self::DIR_NOT_FOUND );
				throw new Exception ( $_msg );
			}
		} catch ( Exception $e ) {
			return FALSE;
		}
	}
	
	// : End
}
